# Virtualmin Extension for Paymenter

This extension allows Paymenter to manage virtual servers in Virtualmin through its remote API.

## Features

- ✅ Create virtual domains/servers
- ✅ Suspend and unsuspend domains
- ✅ Delete domains
- ✅ Update/change plan (template)
- ✅ Generate login links
- ✅ Support for Virtualmin templates

## Requirements

- Virtualmin GPL or Pro installed
- Root access to the Virtualmin server
- Remote API enabled in Virtualmin (enabled by default)
- Port 10000 accessible (default port for Webmin/Virtualmin)

## Installation

1. Copy the `Virtualmin` directory to `extensions/Servers/Virtualmin` in your Paymenter installation
2. Go to the Paymenter administration panel
3. Navigate to Extensions > Servers
4. Find and enable the "Virtualmin" extension

## Configuration

### Extension Configuration

After enabling the extension, configure the following parameters:

- **Hostname**: The complete URL of your Virtualmin server including the port (e.g., `https://server.example.com:10000`)
- **Username**: The master administrator username (usually `root`)
- **Password**: The master administrator password
- **Verify SSL Certificate**: Enable to verify SSL certificates. Disable for self-signed certificates (default: disabled)

### Product Configuration

When creating a product with this extension, you can select:

- **Account Plan**: The Virtualmin plan that will be applied to new domains and will define quotas and limits

### Checkout Configuration

Clients will need to provide:

- **Domain**: The domain name for their virtual server (e.g., `example.com`)

## API Commands Used

This extension uses the following Virtualmin API commands:

- `list-plans` - Lists available account plans
- `list-domains` - Lists domains (used to verify connection)
- `create-domain` - Creates a new virtual domain with basic features (unix, dir, web, dns, mail)
- `disable-domain` - Suspends a domain with reason
- `enable-domain` - Unsuspends a suspended domain
- `delete-domain` - Completely deletes a domain
- `modify-domain` - Modifies a domain and applies new plan with `--apply-plan`
- `create-login-link` - Generates a temporary login link

## Functionality

### Server Creation

When creating a service, the extension:
1. Generates a random username (8 characters)
2. Generates a secure random password
3. Creates the virtual domain in Virtualmin with:
   - Enabled features: Unix user, directory, web, DNS, mail
   - Selected plan (applies limits automatically with `--limits-from-plan`)
   - Client's email as contact
4. Saves credentials in the service properties

### Suspension and Unsuspension

- **Suspension**: Disables the domain using `disable-domain` with reason "Suspended by Paymenter"
- **Unsuspension**: Re-enables the domain using `enable-domain`

### Deletion

Completely deletes the virtual domain and all its associated properties.

### Update/Upgrade

Applies a new plan to the existing domain using `modify-domain` with `--apply-plan`, which automatically updates quotas and limits according to the new plan.

### Available Actions

Clients can view:
- Virtual server username
- Virtual server password
- Assigned domain
- Button to access Virtualmin (generates a temporary login link)

## Future Improvements

This is the base version of the extension. The following improvements are planned:

- [ ] Support for additional Virtualmin features (email, DNS, databases, etc.)
- [ ] Resource configuration options (disk quotas, bandwidth, etc.)
- [ ] Subdomain and alias management
- [ ] MySQL/PostgreSQL database management
- [ ] Email account management
- [ ] DNS record management
- [ ] Script installation (WordPress, etc.)
- [ ] SSL/Let's Encrypt certificate support
- [ ] PHP version configuration

## Reference Documentation

- [Virtualmin Remote API Documentation](https://www.virtualmin.com/documentation/developer/http-api/)
- [Virtualmin Command-line API](https://www.virtualmin.com/documentation/developer/cli/)

## Support

To report issues or suggestions, please open an issue in the Paymenter repository.

## License

This extension is distributed under the GNU General Public License v3.0 (GNU GPLv3).
