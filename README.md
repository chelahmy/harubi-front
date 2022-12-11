# Harubi Front
A javascript view framework for Harubi, which complete the MVC framework. Harubi is a PHP MVC framework minus the view.
The main idea is to remove the view burden from the server. Harubi Front and Harubi made a higher-level separation of
concerns between client and server. Harubi was designed purposely without the view concern. Harubi Front makes it complete.

Harubi Front comes with a functioning user-based application with access control by roles. It also has basic user groups.
The application can be extended. Or, a totally new application can be created using the framework. The application can be
made as an example. There are various reusable components in the application such as table listing and sorting, searching
and filtering, and client-side access control.

The framework is page-based. Every page is based on a pair of a HTML and a Javascript files. A breadcrumb can be set
to navigate from page to page. Communication with a Harubi server is asynchronous. Hence, a page must be ready with
placeholders to accept the asynchronous data from the server.

Please read the pages source codes from the application to get a better idea.

You must be familiar with HTML, Javascript and CSS on the client side, and PHP and MySQL on the server side. Notice that
the client and server are totally separated in term of programming. The communication from the client to the server is
using JQuery. The pages were made using Bootstrap with priority for mobile usage. The server side is using Harubi which
is based on PHP and MySQL.

## Installation

You must have access to your host which must already been installed with PHP and MySQL.

- Copy everything from the **src** folder into your host.
- Create a MySQL database and import *includes/harubi-front.sql*.
- Update the *include/settings.inc* file with your database details.
- Open the *<your-host>/index.html* page in a browser. And click **Provisioning**, and then the **Go** button.
- Go back to the *<your-host>/index.html* page and select **Administration** or **Home**. A sign-in page will open.
Enter *User name* **admin** and *Password* **Admin1234!**, and click the **Sign In** button. The password can be changed
in *My Profile*.

