const ModuleFederationPlugin = require('webpack/lib/container/ModuleFederationPlugin');

module.exports = {
  output: {
    uniqueName: 'shell',
  },
  optimization: {
    runtimeChunk: false,
    minimize: true
  },
  experiments: {
    outputModule: true
  },
  plugins: [
    new ModuleFederationPlugin({

      remotes: {},

      shared: {
        '@angular/animations': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/cdk': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/cdk/table': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/cdk/observers': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/common': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/common/http': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/core': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/forms': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@angular/router': {
          singleton: true,
          requiredVersion: '18.2.8',
          eager: true
        },
        '@apollo/client': {
          singleton: true,
          requiredVersion: '^3.11.8',
          eager: true
        },
        '@ng-bootstrap/ng-bootstrap': {
          singleton: true,
          requiredVersion: '17.0.1',
          eager: true
        },

        '@popperjs/core': {
          singleton: true,
          requiredVersion: '^2.11.8',
          eager: true
        },
        '@swimlane/ngx-charts': {
          singleton: true,
          requiredVersion: '^20.5.0',
          eager: true
        },
        '@tinymce/tinymce-angular': {
          singleton: true,
          requiredVersion: '^8.0.1',
          eager: true
        },
        'angular-svg-icon': {
          singleton: true,
          requiredVersion: '^17.0.0',
          eager: true
        },
        'apollo-angular': {
          singleton: true,
          requiredVersion: '^7.2.0',
          eager: true
        },
        graphql: {
          singleton: true,
          requiredVersion: '^16.9.0',
          eager: true
        },
        'lodash-es': {
          singleton: true,
          requiredVersion: '^4.17.21',
          eager: true
        },

        luxon: {
          singleton: true,
          requiredVersion: '3.5.0',
          eager: true
        },

        mathjs: {
          singleton: true,
          requiredVersion: '^13.2.0',
          eager: true
        },

        'ng-animate': {
          singleton: true,
          requiredVersion: '^2.0.1',
          eager: true
        },
        'ngx-chips': {
          singleton: true,
          requiredVersion: '^3.0.0',
          eager: true
        },

        'primeng': {
          singleton: true,
          requiredVersion: '^17.18.11',
          eager: true

        },

        'rxjs': {
          singleton: true,
          requiredVersion: '^7.8.1',
          eager: true
        },

        'rxjs/operators': {
          singleton: true,
          requiredVersion: '^7.8.1',
          eager: true
        },

        core: {
          singleton: true,
          import: 'dist/core',
          requiredVersion: 'auto',
          eager: true
        },

      }

    }),
  ],
};
