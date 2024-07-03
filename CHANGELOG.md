After version 1.5.2, the nomenclature will be as follows:

X.Y.Z
X: major
Y: Nextcloud version
Z: minor or patch

-----------------------------------------
1.30.0
	- Complete rebuild of the application and change of versioning
	- Have to install php-soap library
1.29.0
	- Complete rebuild of the application and change of versioning
	- Have to install php-soap library
1.28.0
	- Complete rebuild of the application and change of versioning
	- Have to install php-soap library
1.27.0
	- Complete rebuild of the application and change of versioning
1.5.2
	- Market description fix
1.5.1
	- Market description fix
1.5.0
	- Simplification of administration settings
	- Modifications about application information (add links, using conditions)
1.4.1
	- Issue with check settings
1.4.0
	- Add API Key
	- Remove useless parameters
1.3.4
	- Issue with App Signature
1.3.3
	- Update code after WSDL changes
	- Add compatibility for NextCloud v25
1.3.2
	- Add compatibility for NextCloud v24
1.3.1
	- Add sign scope setting
	- Rename signature types (advanced -> standard, qualified -> advanced)
	- Fix signature requests not displayed if shell_exec is disabled on PHP 8
1.3.0
	- Extend the async signature time out setting up to 30 days
	- Add a setting to set the periodicity of the background job that
	  checks for completed signature requests
	- Add pagination on signature requests listings
	- Improve timezone management
	- Various bug fixes and improvements
1.2.1
	- Switch from SoapClient to NuSOAP
	- Fix timezone detection not always working
	- Remove useless setting to ignore SSL/TLS certificate errors
1.2.0
	- Add a demo mode with PDF watermarking
	- Add a meaningful error message when trying to perform an advanced
	  signature on something else than a PDF file
1.1.2
	Fix configuration issues if the application is running on PHP 8
1.1.1
	Retrieve timezone from Nextcloud API to avoid issues caused by SELinux
1.1.0
	Add compatibility for NextCloud v23
1.0.0
     Initial public release.
