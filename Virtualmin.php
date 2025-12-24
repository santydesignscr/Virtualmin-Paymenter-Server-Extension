<?php

namespace Paymenter\Extensions\Servers\Virtualmin;

use App\Classes\Extension\Server;
use App\Models\Service;
use App\Rules\Domain;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Virtualmin extends Server
{
    /**
     * Make an HTTP request to the Virtualmin API
     *
     * @param string $program The API program/command to execute
     * @param array $params Additional parameters for the API call
     * @param bool $json Whether to request JSON output
     * @return \Illuminate\Http\Client\Response
     */
    private function request(string $program, array $params = [], bool $json = true)
    {
        $host = rtrim($this->config('host'), '/');
        $username = $this->config('username');
        $password = $this->config('password');
        
        // Build the URL with parameters
        $url = $host . '/virtual-server/remote.cgi';
        
        // Add program parameter
        $params['program'] = $program;
        
        // Add JSON output format if requested
        if ($json) {
            $params['json'] = '1';
        }
        
        // Make the request with HTTP Basic Auth
        $response = Http::withBasicAuth($username, $password)
            ->withOptions(['verify' => $this->config('verify_ssl', false)])
            ->asForm()
            ->post($url, $params)
            ->throw();
        
        return $response;
    }

    /**
     * Get all the configuration for the extension
     *
     * @param array $values
     * @return array
     */
    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'host',
                'type' => 'text',
                'label' => 'Hostname',
                'placeholder' => 'https://example.com:10000',
                'validation' => 'url:http,https',
                'required' => true,
                'description' => 'The full URL to your Virtualmin server including port (usually 10000)',
            ],
            [
                'name' => 'username',
                'type' => 'text',
                'placeholder' => 'root',
                'label' => 'Username',
                'required' => true,
                'description' => 'The master administrator username (usually root)',
            ],
            [
                'name' => 'password',
                'type' => 'password',
                'placeholder' => '••••••••••••',
                'label' => 'Password',
                'required' => true,
                'description' => 'The password for the master administrator',
            ],
            [
                'name' => 'verify_ssl',
                'type' => 'boolean',
                'label' => 'Verify SSL Certificate',
                'default' => false,
                'description' => 'Enable to verify SSL certificates. Disable for self-signed certificates.',
            ],
        ];
    }

    /**
     * Get product config
     *
     * @param array $values
     * @return array
     */
    public function getProductConfig($values = []): array
    {
        try {
            // Get all plans
            $response = $this->request('list-plans', ['multiline' => '']);
            $data = $response->json();
            
            $planOptions = [];
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $plan) {
                    $planName = $plan['name'] ?? $plan['id'] ?? null;
                    if ($planName) {
                        $planOptions[] = [
                            'value' => $planName,
                            'label' => $planName,
                        ];
                    }
                }
            }
            
            // If no plans found, add a default option
            if (empty($planOptions)) {
                $planOptions[] = [
                    'value' => '',
                    'label' => 'No Plan (Use default limits)',
                ];
            }
            
            return [
                [
                    'name' => 'plan',
                    'type' => 'select',
                    'label' => 'Account Plan',
                    'options' => $planOptions,
                    'required' => false,
                    'description' => 'The Virtualmin plan to use for new domains',
                ],
            ];
        } catch (Exception $e) {
            // If we can't fetch plans, return a text input as fallback
            return [
                [
                    'name' => 'plan',
                    'type' => 'text',
                    'label' => 'Plan Name',
                    'placeholder' => '',
                    'required' => false,
                    'description' => 'The Virtualmin plan to use (leave empty for default)',
                ],
            ];
        }
    }

    /**
     * Check if current configuration is valid
     *
     * @return bool|string
     */
    public function testConfig(): bool|string
    {
        try {
            $response = $this->request('list-domains');
            
            if (!$response->successful()) {
                return 'Failed to connect to Virtualmin server';
            }
            
            $data = $response->json();
            
            // Check if the response has the expected structure
            if (!isset($data['status']) || $data['status'] !== 'success') {
                return $data['error'] ?? 'Unknown error occurred';
            }
            
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return [
            [
                'name' => 'domain',
                'type' => 'text',
                'label' => 'Domain',
                'required' => true,
                'validation' => [new Domain, 'required'],
                'placeholder' => 'example.com',
                'description' => 'The domain name for your virtual server',
            ],
        ];
    }

    /**
     * Create a virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return bool
     */
    public function createServer(Service $service, $settings, $properties)
    {
        if (!isset($properties['domain'])) {
            throw new Exception('Domain is required');
        }
        
        // Generate a random username (8 characters)
        $username = Str::random(8);
        // Ensure it starts with a letter
        if (is_numeric($username[0])) {
            $username = 'u' . substr($username, 1);
        }
        $username = strtolower($username);
        
        // Generate a random password
        $password = Str::random(16);
        
        // Prepare the parameters for domain creation
        $params = [
            'domain' => $properties['domain'],
            'user' => $username,
            'pass' => $password,
            'email' => $service->user->email,
            'unix' => '',
            'dir' => '',
            'web' => '',
            'dns' => '',
            'mail' => '',
            'limits-from-plan' => '',
        ];
        
        // Add plan if specified
        if (isset($settings['plan']) && !empty($settings['plan'])) {
            $params['plan'] = $settings['plan'];
        }
        
        // Add description
        $params['desc'] = 'Created by Paymenter for ' . $service->user->email;
        
        try {
            $response = $this->request('create-domain', $params);
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception($data['error'] ?? 'Failed to create virtual server');
            }
            
            // Store the service properties
            $service->properties()->updateOrCreate(
                ['key' => 'virtualmin_username'],
                [
                    'name' => 'Username',
                    'value' => $username,
                ]
            );
            
            $service->properties()->updateOrCreate(
                ['key' => 'virtualmin_password'],
                [
                    'name' => 'Password',
                    'value' => $password,
                    'hidden' => false,
                ]
            );
            
            $service->properties()->updateOrCreate(
                ['key' => 'virtualmin_domain'],
                [
                    'name' => 'Domain',
                    'value' => $properties['domain'],
                ]
            );
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to create Virtualmin domain: ' . $e->getMessage());
        }
    }

    /**
     * Suspend a virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return bool
     */
    public function suspendServer(Service $service, $settings, $properties)
    {
        if (!isset($properties['virtualmin_domain'])) {
            throw new Exception('Service has not been created');
        }
        
        try {
            $response = $this->request('disable-domain', [
                'domain' => $properties['virtualmin_domain'],
                'why' => 'Suspended by Paymenter',
                'subservers' => '',
            ]);
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception($data['error'] ?? 'Failed to suspend virtual server');
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to suspend Virtualmin domain: ' . $e->getMessage());
        }
    }

    /**
     * Unsuspend a virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return bool
     */
    public function unsuspendServer(Service $service, $settings, $properties)
    {
        if (!isset($properties['virtualmin_domain'])) {
            throw new Exception('Service has not been created');
        }
        
        try {
            $response = $this->request('enable-domain', [
                'domain' => $properties['virtualmin_domain'],
                'subservers' => '',
            ]);
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception($data['error'] ?? 'Failed to unsuspend virtual server');
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to unsuspend Virtualmin domain: ' . $e->getMessage());
        }
    }

    /**
     * Terminate a virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return bool
     */
    public function terminateServer(Service $service, $settings, $properties)
    {
        if (!isset($properties['virtualmin_domain'])) {
            throw new Exception('Service has not been created');
        }
        
        try {
            $response = $this->request('delete-domain', [
                'domain' => $properties['virtualmin_domain'],
            ]);
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception($data['error'] ?? 'Failed to delete virtual server');
            }
            
            // Delete the properties
            $service->properties()->whereIn('key', [
                'virtualmin_username',
                'virtualmin_password',
                'virtualmin_domain',
            ])->delete();
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to delete Virtualmin domain: ' . $e->getMessage());
        }
    }

    /**
     * Upgrade a virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return bool
     */
    public function upgradeServer(Service $service, $settings, $properties)
    {
        if (!isset($properties['virtualmin_domain'])) {
            throw new Exception('Service has not been created');
        }
        
        try {
            $params = [
                'domain' => $properties['virtualmin_domain'],
            ];
            
            // Update plan if specified (this applies quotas and limits from the plan)
            if (isset($settings['plan']) && !empty($settings['plan'])) {
                $params['apply-plan'] = $settings['plan'];
            }
            
            $response = $this->request('modify-domain', $params);
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception($data['error'] ?? 'Failed to upgrade virtual server');
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to upgrade Virtualmin domain: ' . $e->getMessage());
        }
    }

    /**
     * Get login URL for the virtual server
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return string
     */
    public function getLoginUrl(Service $service, $settings, $properties): string
    {
        if (!isset($properties['virtualmin_domain']) || !isset($properties['virtualmin_username'])) {
            throw new Exception('Service has not been created');
        }
        
        try {
            // Use create-login-link API to generate a temporary login URL
            $response = $this->request('create-login-link', [
                'domain' => $properties['virtualmin_domain'],
            ]);
            
            $data = $response->json();
            
            if (isset($data['data']) && isset($data['data']['url'])) {
                return $data['data']['url'];
            }
            
            // Fallback: return the Virtualmin URL with domain info
            $host = rtrim($this->config('host'), '/');
            return $host . '/virtual-server/';
            
        } catch (Exception $e) {
            // If create-login-link fails, return the base Virtualmin URL
            $host = rtrim($this->config('host'), '/');
            return $host . '/virtual-server/';
        }
    }

    /**
     * Get actions for the service
     *
     * @param Service $service
     * @param array $settings (product settings)
     * @param array $properties (checkout options)
     * @return array
     */
    public function getActions(Service $service, $settings, $properties): array
    {
        if (!isset($properties['virtualmin_domain'])) {
            return [];
        }
        
        return [
            [
                'label' => 'Username',
                'text' => $properties['virtualmin_username'] ?? 'N/A',
                'type' => 'text',
            ],
            [
                'label' => 'Password',
                'text' => $properties['virtualmin_password'] ?? 'N/A',
                'type' => 'text',
            ],
            [
                'label' => 'Domain',
                'text' => $properties['virtualmin_domain'] ?? 'N/A',
                'type' => 'text',
            ],
            [
                'label' => 'Access Virtualmin',
                'type' => 'button',
                'function' => 'getLoginUrl',
            ],
        ];
    }
}
