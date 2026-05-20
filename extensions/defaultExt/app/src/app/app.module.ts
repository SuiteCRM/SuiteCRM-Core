import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';
import {AppComponent} from './app.component';
import {ExtensionModule} from '../extension.module';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

@NgModule({ declarations: [
        AppComponent
    ],
    bootstrap: [AppComponent], imports: [BrowserModule,
        ExtensionModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
export class AppModule {
}
