/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

import {Component, HostListener, inject, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {BehaviorSubject, combineLatest, combineLatestWith, Observable, Subscription} from 'rxjs';
import {filter, map} from 'rxjs/operators';
import {ViewContext} from '../../../../common/views/view.model';
import {WidgetMetadata} from '../../../../common/metadata/widget.metadata';
import {MetadataStore, RecordViewSectionMetadata} from '../../../../store/metadata/metadata.store.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {
    SubpanelContainerConfig
} from '../../../../containers/subpanel/components/subpanel-container/subpanel-container.model';
import {SidebarWidgetAdapter} from '../../adapters/sidebar-widget.adapter';
import {RecordViewStore} from '../../store/record-view/record-view.store';
import {RecordContentAdapter} from '../../adapters/record-content.adapter';
import {RecordContentDataSource} from '../../../../components/record-content/record-content.model';
import {TopWidgetAdapter} from '../../adapters/top-widget.adapter';
import {BottomWidgetAdapter} from '../../adapters/bottom-widget.adapter';
import {RecordActionsAdapter} from '../../adapters/actions.adapter';
import {Action, ActionContext} from '../../../../common/actions/action.model';
import {RecordViewSidebarWidgetService} from "../../services/record-view-sidebar-widget.service";
import {ActivatedRoute} from "@angular/router";
import {Panel, PanelRow} from "../../../../common/metadata/metadata.model";
import {isEmpty} from "lodash-es";
import {FieldActionsAdapterFactory} from "../../../../components/field-layout/adapters/field.actions.adapter.factory";
import {PanelLogicManager} from "../../../../components/panel-logic/panel-logic.manager";
import {RecordContentAdapterFactory} from "../../adapters/record-content.adapter.factory";
import {SubpanelStoreMap} from "../../../../containers/subpanel/store/subpanel/subpanel.store";
import {HeaderWidgetAdapter} from "../../adapters/header-widget.adapter";

@Component({
    selector: 'scrm-record-container',
    templateUrl: 'record-container.component.html',
    providers: [TopWidgetAdapter, SidebarWidgetAdapter, BottomWidgetAdapter, HeaderWidgetAdapter]
})
export class RecordContainerComponent implements OnInit, OnDestroy {

    @Input() section: string = '';

    protected subs: Subscription[] = [];

    saveAction: Action;
    context: ActionContext;
    loading: boolean = true;
    isOffsetExist: boolean = false;
    displaySidebar: WritableSignal<boolean> = signal(true);
    showTopWidget: WritableSignal<boolean> = signal(true);
    showSidebarWidgets: WritableSignal<boolean> = signal(true);
    showBottomWidgets: WritableSignal<boolean> = signal(true);
    showHeaderWidgets: WritableSignal<boolean> = signal(true);
    showSubpanels: WritableSignal<boolean> = signal(true);
    swapWidgets: WritableSignal<boolean> = signal(false);
    topWidgetConfig: WritableSignal<any> = signal(null);
    sidebarWidgetConfig: WritableSignal<any> = signal(null);
    bottomWidgetConfig: WritableSignal<any> = signal(null);
    headerWidgetConfig: WritableSignal<any> = signal(null);

    panels: WritableSignal<Panel[]> = signal([]);
    panels$: Observable<Panel[]>;
    displayedSubpanelsKeys: WritableSignal<string[]> = signal([]);
    displayedSubpanels$: Observable<SubpanelStoreMap>;
    protected panelsSubject: BehaviorSubject<Panel[]> = new BehaviorSubject(this.panels());
    protected fieldsActionAdaptorFactory: FieldActionsAdapterFactory;
    protected fieldSubs: Subscription[] = [];
    protected panelLogicManager: PanelLogicManager;
    protected contentAdapter: RecordContentAdapter;
    protected contentAdapterFactory: RecordContentAdapterFactory;

    actionConfig$ = this.recordViewStore.mode$.pipe(
        combineLatestWith(
            this.actionsAdapter.getActions(),
            this.getViewContext$()),
        filter(([mode, actions, context]) => mode === 'edit'),
        map(([mode, actions, context]) => ({
            mode,
            actions,
            context
        }))
    );

    @HostListener('keyup.control.enter')
    onEnterKey() {
        if (!this.saveAction || !this.context) {
            return;
        }
        this.actionsAdapter.runAction(this.saveAction, this.context);
    }

    constructor(
        public recordViewStore: RecordViewStore,
        protected language: LanguageStore,
        protected metadata: MetadataStore,
        protected topWidgetAdapter: TopWidgetAdapter,
        protected sidebarWidgetAdapter: SidebarWidgetAdapter,
        protected bottomWidgetAdapter: BottomWidgetAdapter,
        protected headerWidgetAdapter: HeaderWidgetAdapter,
        protected actionsAdapter: RecordActionsAdapter,
        protected sidebarWidgetHandler: RecordViewSidebarWidgetService,
        protected languageStore: LanguageStore,
        private activatedRoute: ActivatedRoute,
    ) {
        const queryParams = this.activatedRoute.snapshot.queryParamMap;
        this.isOffsetExist = !!queryParams.get('offset');
        this.panels$ = this.panelsSubject.asObservable();

        this.fieldsActionAdaptorFactory = inject(FieldActionsAdapterFactory);
        this.panelLogicManager = inject(PanelLogicManager);
        this.contentAdapterFactory = inject(RecordContentAdapterFactory);
        this.contentAdapter = this.contentAdapterFactory.create(this.recordViewStore, this.panels$);

        this.initPanels();
    }

    ngOnInit(): void {
        this.subs.push(this.recordViewStore.loading$.subscribe(loading => {
            this.loading = loading;
        }));

        this.subs.push(this.actionConfig$.subscribe(config => {
                this.context = config.context;
                config.actions.forEach(actionItem => {
                    if (actionItem.key === 'save') {
                        this.saveAction = actionItem;
                    }
                });
            })
        );

        this.subs.push(this.topWidgetAdapter.config$.subscribe(topWidgetConfig => {
            this.topWidgetConfig.set({...topWidgetConfig});
            this.showTopWidget.set(topWidgetConfig?.show ?? false);
            this.displaySidebar.set(this.showSidebarWidgets() || this.showTopWidget());
        }));

        this.subs.push(this.sidebarWidgetAdapter.config$.subscribe(sidebarWidgetConfig => {
            this.sidebarWidgetConfig.set({...sidebarWidgetConfig});
            this.showSidebarWidgets.set(sidebarWidgetConfig?.show ?? false);
            this.displaySidebar.set(sidebarWidgetConfig?.showSidebarWidgets && (this.showSidebarWidgets() || this.showTopWidget()));
        }));

        this.subs.push(this.bottomWidgetAdapter.config$.subscribe(bottomWidgetConfig => {
            this.bottomWidgetConfig.set({...bottomWidgetConfig});
            this.showBottomWidgets.set(bottomWidgetConfig?.show ?? false);
        }));

        this.subs.push(this.headerWidgetAdapter.config$.subscribe(headerWidgetConfig => {
            this.headerWidgetConfig.set({...headerWidgetConfig});
            this.showHeaderWidgets.set(headerWidgetConfig?.show ?? false);
        }));

        this.subs.push(this.sidebarWidgetHandler.widgetSwap$.subscribe(swap => {
            this.swapWidgets.set(swap);
        }));

        this.subs.push(this.recordViewStore.showSubpanels$.subscribe(showSubpanels => {
            this.showSubpanels.set(showSubpanels);
        }));

        this.displayedSubpanels$ = this.recordViewStore.subpanels$.pipe(
            combineLatestWith(this.recordViewStore.sectionMetadata$),
            map(([subpanels, sectionMetadata] : [SubpanelStoreMap, RecordViewSectionMetadata]) => {

                const filteredSubpanels = {} as SubpanelStoreMap;
                const subpanelsToDisplay = sectionMetadata?.subpanels ?? [];


                if (!subpanelsToDisplay.length) {
                    this.displayedSubpanelsKeys.set([]);
                    return filteredSubpanels;
                }

                subpanelsToDisplay.forEach(subpanelKey => {
                    if (subpanels[subpanelKey]) {
                        filteredSubpanels[subpanelKey] = subpanels[subpanelKey];
                    }
                });

                this.displayedSubpanelsKeys.set(Object.keys(filteredSubpanels));
                return filteredSubpanels;
            })
        );

        this.subs.push(this.displayedSubpanels$.subscribe());
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
        this.contentAdapter.clean();
        this.fieldSubs = this.safeUnsubscription(this.fieldSubs);
    }

    getContentAdapter(): RecordContentDataSource {
        return this.contentAdapter;
    }

    getSubpanelsConfig(): SubpanelContainerConfig {
        return {
            parentModule: this.recordViewStore.getModuleName(),
            subpanels$: this.displayedSubpanels$,
            sidebarActive$: this.recordViewStore.widgets$
        } as SubpanelContainerConfig;
    }

    getViewContext(): ViewContext {
        return this.recordViewStore.getViewContext();
    }

    getViewContext$(): Observable<ViewContext> {
        return this.recordViewStore.viewContext$;
    }

    hasTopWidgetMetadata(meta: WidgetMetadata): boolean {
        return !!(meta && meta.type);
    }

    protected initPanels(): void {
        const panelSub = combineLatest([
            this.recordViewStore.sectionMetadata$,
            this.recordViewStore.stagingRecord$,
            this.languageStore.vm$,
        ]).subscribe(([meta, record, languages]) => {
            const panels = [];
            const module = (record && record.module) || '';

            if (!meta || !meta.panels) {
                return panels;
            }

            this.safeUnsubscription(this.fieldSubs);
            meta.panels.forEach(panelDefinition => {
                const label = (panelDefinition.label)
                    ? panelDefinition.label.toUpperCase()
                    : this.languageStore.getFieldLabel(panelDefinition.key.toUpperCase(), module, languages);
                const panel = {label, key: panelDefinition.key, rows: []} as Panel;


                let adaptor = null;
                const tabDef = meta.templateMeta.tabDefs[panelDefinition.key.toUpperCase()] ?? null;
                if (tabDef) {
                    panel.meta = tabDef;
                }

                panelDefinition.rows.forEach(rowDefinition => {
                    const row = {cols: []} as PanelRow;
                    rowDefinition.cols.forEach(cellDefinition => {
                        const cellDef = {...cellDefinition};
                        const fieldActions = cellDefinition.fieldActions || null;
                        if (fieldActions) {
                            adaptor = this.fieldsActionAdaptorFactory.create('recordView', cellDef.name, this.recordViewStore);
                            cellDef.adaptor = adaptor;
                        }
                        row.cols.push(cellDef);
                    });
                    panel.rows.push(row);
                });

                panel.displayState = new BehaviorSubject(tabDef?.display ?? true);
                panel.display$ = panel.displayState.asObservable();

                panels.push(panel);

                if (isEmpty(record?.fields) || isEmpty(tabDef?.displayLogic)) {
                    return;
                }

                Object.values(tabDef.displayLogic).forEach((logicDef) => {
                    if (isEmpty(logicDef?.params?.fieldDependencies)) {
                        return;
                    }

                    logicDef.params.fieldDependencies.forEach(fieldKey => {
                        const field = record.fields[fieldKey] || null;
                        if (isEmpty(field)) {
                            return;
                        }

                        this.fieldSubs.push(
                            field.valueChanges$.subscribe(() => {
                                this.panelLogicManager.runLogic(logicDef.key, field, panel, record, this.recordViewStore.getMode());
                            }),
                        );
                    });
                });
            });
            this.panels.set(panels)
            this.panelsSubject.next(this.panels());
            return panels;
        });

        this.subs.push(panelSub);
    }

    protected safeUnsubscription(subscriptionArray: Subscription[]): Subscription[] {
        subscriptionArray.forEach(sub => {
            if (sub.closed) {
                return;
            }

            sub.unsubscribe();
        });
        subscriptionArray = [];

        return subscriptionArray;
    }
}
