import {Component} from '@angular/core';
import {ImageModule} from "../../../../../components/image/image.module";

@Component({
    selector: 'scrm-history-sidebar-skeleton-loading',
    standalone: true,
    imports: [
        ImageModule
    ],
    templateUrl: './history-sidebar-skeleton-loading.component.html',
})
export class HistorySidebarSkeletonLoadingComponent {

}
