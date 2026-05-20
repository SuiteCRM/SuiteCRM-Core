import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';
@NgModule({ declarations: [], imports: [CommonModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
export class ExtensionModule {
    constructor() {
    }
    init(): void {
    }
}
