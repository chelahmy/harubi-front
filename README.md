# Harubi Front
Every new application development will be based on some sort of frameworks. Harubi Front is a web application development framework. An application is developed to serve specific and perhaps unique purposes. However, to serve its purposes an application may need general fundamental supports such as user management, multi-page navigation, internationalization/translations, data entry and visualization, searching and filtering, database management, etc. Those fundamental supports are now being provided by software development frameworks. Usually, a framework is bigger than the core application itself. A framework will free developers from time consuming fundamental development, and will let them focus on developing the application true purposes. 

Harubi Front intents to fullfill fundamental supports of new web application developments. Harubi Front development itself is on going. Any contribution is welcome.

### Features
- Mobile first web application.
- Multi-pages with mobile friendly menu navigation.
- Runs in browser. No server-side rendering.
- Optimal server hits so less security concerns.
- Multi-users.
- Based on [React](https://reactjs.org) and [Foundation](https://foundation.zurb.com). Both are popular and heavily supported frameworks.

### Example

Please visit [https://harubifront.000webhostapp.com](https://harubifront.000webhostapp.com/) to experience a very basic Harubi Front web application. You may freely sign up and then sign in. It runs in a free shared hosting server provided by [000webhost](https://www.000webhost.com/). The free shared hosting server is used as a yardstick. If it can run in a free shared hosting server then it can run in a more powerful server. A Harubi Front web application runs in a browser. However, the back-end requires [PHP](https://www.php.net/) and [MySQL](https://www.mysql.com/), which in this case manages the user records. The free shared hosting server comes with PHP and MySQL.

### Introduction
Harubi Front is a front-end web applications development framework based on [React](https://reactjs.org) and [Foundation](https://foundation.zurb.com), and loosely coupled with harubi back-end. [Harubi](https://github.com/chelahmy/harubi) is all about back-ends. Initially, Harubi Front was an attempt for a harubi front-end. In fact, Harubi Front does not really rely on harubi for back-end. Decoupling front-end and back-end prevents unneeded code fusion, and loosens unnecessary intertwined concerns of development. Generally a back-end can serve multiple front-ends such as for web, mobile and IoT.

Harubi Front renders front-end with javascript. It uses React component-based structuring to render dynamic user interfaces and contents. It makes minimal calls to back-end. Harubi Front is optimized for mobile web.

The following image is a screenshot of a typical Harubi Front application:

![A Harubi Front application](docs/harubi-front-home.png)

**Framework modules**: React is used for structuring. And Foundation is used for micro-structuring and theming. Analogously, React is used to construct a building, and Foundation is used for finishing. Both of them make a powerful front-end framework. Coupling with harubi [model-action](https://github.com/chelahmy/harubi/tree/master/templates/models) pattern all of them make a great development framework.

**React and Foundation**: Forms for signing up, signing in and other actions are based on Foundation Form and Abide. The top bar and its menus are also based on Foundation. The responsive styling is also based on Foundation. Anything visible is being styled with Foundation. However, underneath all of them is a structure based on crafted React components. Harubi Front development always starts with React structuring.

Harubi Front is an extension of [Foundation-React Template](https://github.com/chelahmy/foundation-react-template). Please refer to the Foundation-React Template documentation for details on dependencies and tool-chain. This extension added the [PHP development server](https://www.php.net/manual/en/features.commandline.webserver.php) to [Browsersync](https://browsersync.io/).

## Prerequisites
The tool-chain requires [npm](https://www.npmjs.com/) which comes together with [nodejs](https://nodejs.org/en/). However, they are not required for deployment. Npm will be used to download all other dependencies for the build tool-chain. [Git](https://git-scm.com/) is used to clone this repository, or it can just be downloaded here. The harubi back-end requires [PHP](https://www.php.net/) and [MySQL](https://www.mysql.com/), both during development and deployment. 

## Installation
Clone this repository into a folder
```
$ git clone https://github.com/chelahmy/harubi-front.git my-project
$ cd my-project
```
and run the following command to download all dependencies including Foundation and React.
```
$ npm i
```

### Database setup
Create a MySQL database and update the `src/backend/settings.inc` file with the new database settings. Then create the `user` table using the `src/backend/user.install.sql` script. Please refer to [harubi](https://github.com/chelahmy/harubi) for more details.

## How-to
Start the project.
```
$ npm start
```
The tool-chain will build the project into the `dist` folder and run it in a browser. Any changes made in the `src` folder will trigger the tool-chain to rebuild the project and push the changes to the browser in real time. Remove the `dist` folder to rebuild the project.

Begin with the `src/index.html`, `src/js/app.js`, `src/scss/app.scss` and `src/backend/serve.php` files. The resulting `dist` is standalone and can be deployed to a PHP and MySQL web server.

For building the project without watching the changes in the source files and triggering the browser
```
$ npm run build
```
