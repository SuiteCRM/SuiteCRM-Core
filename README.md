<a href="https://suitecrm.com">
  <img width="180px" height="41px" src="https://suitecrm.com/wp-content/uploads/2017/12/logo.png" align="right" />
</a>

# SuiteCRM 8.8.1

[![LICENSE](https://img.shields.io/github/license/suitecrm/suitecrm.svg)](https://github.com/salesagility/suitecrm/blob/hotfix/LICENSE.txt)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/salesagility/SuiteCRM-Core/issues)
[![Gitter chat](https://badges.gitter.im/gitterHQ/gitter.png)](https://gitter.im/suitecrm/Lobby)
[![Twitter](https://img.shields.io/twitter/follow/suitecrm.svg?style=social&label=Follow)](https://twitter.com/intent/follow?screen_name=suitecrm)

[Website](https://suitecrm.com) | 
[Maintainers](https://salesagility.com) |
[Community & Forum](https://suitecrm.com/suitecrm/forum) |
[Code of Conduct](https://docs.suitecrm.com/community/code-of-conduct/)

[SuiteCRM](https://suitecrm.com) is the award-winning open-source, enterprise-ready Customer Relationship Management (CRM) software application.

Our vision is to be the most adopted open source enterprise CRM in the world, giving users full control of their data and freedom to own and customise their business solution.

Find out more about SuiteCRM 8 and checkout the online demo [here](https://suitecrm.com/suitecrm8/)

### Getting Started ###

Visit the [Administration Guide](https://docs.suitecrm.com/8.x/admin/) for details on getting started, system compatibility, and installing SuiteCRM 8.

**Prerequisites:**
- PHP version 7.3 or later
- MySQL version 5.7 or later
- Apache or Nginx web server

**Step-by-Step Installation:**
1. Download the latest release of SuiteCRM from the [GitHub Releases page](https://github.com/salesagility/SuiteCRM/releases).
2. Unzip the downloaded file into your desired directory on the server.
3. Set the correct file permissions to ensure your web server can access the SuiteCRM files:
    - Use `chown` and `chmod` commands to set ownership and permissions appropriately.
4. Access the installation wizard by navigating to your server’s address (e.g., `http://localhost/suitecrm`) in your browser.
5. Follow the on-screen instructions to complete the installation process.

**Troubleshooting Tips:**
- If you encounter permission issues during installation, verify the web server has access rights to the SuiteCRM files.
- If database connection problems arise, confirm that the MySQL service is running and that you are using correct credentials.

See the [Release Notes](https://docs.suitecrm.com/8.x/admin/releases/) for more detail and known issues in the beta build.

---

### Development Environment Setup ###

For contributors who wish to fork and modify SuiteCRM, setting up a development environment is straightforward. Follow these steps:

1. Clone the repository: `git clone https://github.com/salesagility/SuiteCRM.git`.
2. Navigate into the SuiteCRM directory.
3. Install the necessary dependencies using `composer install` and `npm install`.
4. Set up a local database and run migrations using the required commands.
5. Run the development server for testing using `npm run dev`.

For detailed setup instructions, visit the [Developer Guide](https://docs.suitecrm.com/8.x/developer-guide/).

---

### Contribute ###

We would love to have your feedback and input to help make SuiteCRM 8 great for everyone.

SuiteCRM 8 is still in active development, and all current releases are not yet production-ready, so be sure to check the [Release Notes and list of Known Issues](https://docs.suitecrm.com/8.x/admin/releases/) before getting involved.

**How to Contribute:**
1. Fork the repository and clone it to your local machine.
2. Create a new branch for your changes using `git checkout -b your-feature-branch`.
3. Make your changes in your local environment, following the coding standards outlined in the documentation.
4. Submit a pull request (PR) once your changes are complete by following the GitHub workflow.

If you have found an issue you think we should know about, or have a suggestion/feedback, please [Submit An Issue](https://github.com/salesagility/SuiteCRM-Core/issues).

If you want to get involved or submit a Fix, fork the repo and when ready, please [Submit A PR](https://github.com/salesagility/SuiteCRM-Core/pulls) – more details for developers will be coming soon, so stay tuned.

---

### Security ###

We take security seriously here at SuiteCRM, so if you have discovered a security risk, report it by emailing [security@suitecrm.com](mailto:security@suitecrm.com). This will be delivered to the product team who handle security issues.

Please don't disclose security bugs publicly until they have been handled by the security team.

Your email will be acknowledged within 24 hours during the business week (Mon - Fri), and you’ll receive a more detailed response to your email within 72 hours during the business week (Mon - Fri) indicating the next steps in handling your report.

---

### Support ###

SuiteCRM is an open-source project. If you require help with support, then please use our [support forum](https://suitecrm.com/suitecrm/forum/). By using the forums, the knowledge is shared with everyone in the community. Our developer and community team members answer questions on the forum daily, but it also allows the other members of the community to contribute. If you would like customisations to specifically fit your SuiteCRM needs, then please visit the [website](https://suitecrm.com/).

---

### License [![AGPLv3](https://img.shields.io/github/license/suitecrm/suitecrm.svg)](./LICENSE.txt)

SuiteCRM is published under the AGPLv3 license.
