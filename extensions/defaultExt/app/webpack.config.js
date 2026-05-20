const ModuleFederationPlugin = require('webpack/lib/container/ModuleFederationPlugin');


module.exports = {
  output: {
    publicPath: 'auto',
    uniqueName: 'defaultExt'
  },
  optimization: {
    runtimeChunk: false
  },
  experiments: {
    outputModule: true
  },
  plugins: [
    new ModuleFederationPlugin({

      name: 'defaultExt',
      filename: 'remoteEntry.js',
      library: {
        type: "module",
      },
      exposes: {
        './Module': './extensions/defaultExt/app/src/extension.module.ts'
      },

      shared: {
        '@angular/animations': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/cdk': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/cdk/table': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/cdk/observers': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/common': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/common/http': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/core': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/forms': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@angular/router': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@apollo/client': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@ng-bootstrap/ng-bootstrap': {
          singleton: true,
          requiredVersion: 'auto'
        },

        '@popperjs/core': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@swimlane/ngx-charts': {
          singleton: true,
          requiredVersion: 'auto'
        },
        '@tinymce/tinymce-angular': {
          singleton: true,
          requiredVersion: 'auto'
        },
        'angular-svg-icon': {
          singleton: true,
          requiredVersion: 'auto'
        },
        'apollo-angular': {
          singleton: true,
          requiredVersion: 'auto'
        },
        graphql: {
          singleton: true,
          requiredVersion: 'auto'
        },
        'lodash-es': {
          singleton: true,
          requiredVersion: 'auto'
        },

        luxon: {
          singleton: true,
          requiredVersion: 'auto'
        },

        mathjs: {
          singleton: true,
          requiredVersion: 'auto'
        },

        'ng-animate': {
          singleton: true,
          requiredVersion: 'auto'
        },
        'ngx-chips': {
          singleton: true,
          requiredVersion: 'auto'
        },

        'primeng': {
          singleton: true,
          requiredVersion: 'auto'

        },

        'rxjs': {
          singleton: true,
          requiredVersion: 'auto'
        },

        'rxjs/operators': {
          singleton: true,
          requiredVersion: 'auto'
        },

        core: {
          singleton: true,
          import: 'dist/core',
          requiredVersion: 'auto'
        },

      }

    }),
  ],
};
