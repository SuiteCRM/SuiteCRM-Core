/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

import {isArray, isEmpty} from 'lodash-es';
import {DateTime} from 'luxon';
import {isEmptyString} from '../../../common/utils/value-utils';
import {FieldDefinitionMap} from '../../../common/record/field.model';
import {SearchCriteria, SearchCriteriaFieldFilter} from '../../../common/views/list/search-criteria.model';
import {Injectable} from '@angular/core';
import {Params} from '@angular/router';
import {SavedFilter} from '../../../store/saved-filters/saved-filter.model';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';
import {DataTypeFormatter} from '../../../services/formatters/data-type.formatter.service';

type GenericMap<T> = { [key: string]: T };
type NestedGenericMap<T> = GenericMap<GenericMap<T>>;
type DoubleNestedGenericMap<T> = GenericMap<NestedGenericMap<T>>;

const MONTH_YEAR_REGEX = new RegExp('^(\\d{4})-(0[1-9]|1[0-2])$');
const MONTH_REGEX = new RegExp('^(\\d{4})$');

@Injectable({ providedIn: 'root' })
export class ListViewUrlQueryService {

    /**
     * Array of allowed properties to be set to the searchCriteriaFieldFilter from url_query_filter_mapping
     */
    private allowedProperties = [
        'operator',
        'target',
        'values',
        'start',
        'end'
    ];

    /**
     * An array containing properties that can be converted into dbFormat.
     */
    private convertableProperties = [
        'target',
        'values',
        'start',
        'end'
    ];

    constructor (
        protected systemConfig: SystemConfigStore,
        protected metadataStore: MetadataStore,
        protected dataTypeFormatter: DataTypeFormatter
    ) {
    }

    /**
     * Builds a URL query-based filter.
     *
     * @param {string} module - The module name.
     * @param {SavedFilter} defaultFilter - The default filter.
     * @param {Params} rawQueryParams - The raw query parameters.
     * @returns {SavedFilter|null} - The built URL query-based filter, or null if no filter criteria are found.
     */
    public buildUrlQueryBasedFilter (
        module: string,
        defaultFilter: SavedFilter,
        rawQueryParams: Params
    ): SavedFilter | null {
        const filterFieldDefinitions = this.metadataStore.get().recordView.vardefs;

        const queryParams = Object.entries(rawQueryParams)
            .reduce((acc, [queryParamKey, queryParamVal]) => {
                const [cleanQueryParamKey, cleanQueryParamVal] = this.cleanQueryParam([
                    queryParamKey,
                    queryParamVal]);
                acc[cleanQueryParamKey] = cleanQueryParamVal;
                return acc;
            }, {} as Params);

        const filterCriteria: SearchCriteria = this.getQueryFilterCriteria(
            filterFieldDefinitions,
            module,
            queryParams
        );

        if (isEmpty(filterCriteria.filters)) {
            return null;
        }

        return {
            key: 'default',
            searchModule: module,
            module: 'saved-search',
            criteria: filterCriteria
        } as SavedFilter;
    }

    /**
     * Generates the query filter criteria based on the provided field definitions map, module, and query parameters.
     *
     * @param {FieldDefinitionMap} fieldDefinitionMap - The field definition map.
     * @param {string} module - The module name.
     * @param {Params} queryParams - The query parameters.
     * @returns {SearchCriteria} - The generated search criteria.
     * @protected
     */
    protected getQueryFilterCriteria (
        fieldDefinitionMap: FieldDefinitionMap,
        module: string,
        queryParams: Params
    ): SearchCriteria {
        const criteria: SearchCriteria = {
            name: 'default',
            filters: {}
        } as SearchCriteria;

        const queryParamsKeys = Object.keys(queryParams);
        const fieldDefinitions = Object.values(fieldDefinitionMap)
            .filter(({ name }) => queryParamsKeys.some(qPKey => qPKey.includes(name)));

        const listviewUrlQueryFilterMapping = this.systemConfig.getConfigValue(
            'listview_url_query_filter_mapping'
        ) as DoubleNestedGenericMap<string>;
        const listviewUrlQueryFilterMappingEntries = Object.entries(listviewUrlQueryFilterMapping);
        listviewUrlQueryFilterMappingEntries.push(['', {}]);

        let searchType;
        switch (queryParams['searchFormTab']) {
            case 'basic_search':
                searchType = 'basic';
                break;
            case 'advanced_search':
                searchType = 'advanced';
                break;
            default:
                searchType = 'advanced';
        }

        for (const fieldDefinition of fieldDefinitions) {
            const fieldFilterName = fieldDefinition.name;
            const fieldFilterKeys = [
                fieldFilterName,
                `${fieldFilterName}_${searchType}`
            ];

            for (const [queryFilterOperatorKeyTemplate, queryFilterOperatorParamsMap] of listviewUrlQueryFilterMappingEntries) {
                if (!isEmpty(criteria.filters[fieldFilterName])) {
                    break;
                }

                for (const fieldFilterKey of fieldFilterKeys) {
                    if (!isEmpty(criteria.filters[fieldFilterName])) {
                        break;
                    }

                    const searchCriteriaFieldFilter = this.buildSearchCriteriaFieldFilter(
                        fieldFilterName,
                        fieldDefinition.type,
                        queryParams,
                        fieldFilterKey,
                        queryFilterOperatorKeyTemplate,
                        queryFilterOperatorParamsMap
                    );

                    if (isEmpty(searchCriteriaFieldFilter)) {
                        continue;
                    }

                    try {
                        this.convertableProperties.forEach((convertableProperty) => {
                            if (!searchCriteriaFieldFilter[convertableProperty]) {
                                return;
                            }

                            let internalFormatValue;
                            if (isArray(searchCriteriaFieldFilter[convertableProperty])) {
                                internalFormatValue = searchCriteriaFieldFilter[convertableProperty].map(
                                    prop => this.toInternalFormat(
                                        fieldDefinition.type,
                                        prop
                                    ));
                            } else {
                                internalFormatValue = this.toInternalFormat(
                                    fieldDefinition.type,
                                    searchCriteriaFieldFilter[convertableProperty]
                                );
                            }

                            searchCriteriaFieldFilter[convertableProperty] = internalFormatValue;
                        });
                    } catch (e) {
                        continue;
                    }

                    criteria.filters[fieldFilterName] = searchCriteriaFieldFilter;
                }
            }
        }

        return criteria;
    }

    /**
     * Builds a search criteria field filter object based on the provided parameters.
     *
     * @param {string} fieldFilterName - The name of the field filter.
     * @param {string} fieldFilterFieldType - The type of the field filter.
     * @param {Params} queryParams - The query parameters.
     * @param {string} fieldFilterKey - The key of the field filter in the query parameters.
     * @param {string} queryFilterOperatorKeyTemplate - The template for the query filter operator key.
     * @param {NestedGenericMap<string>} queryFilterOperatorParamsMap - The map of query filter operator keys to their respective parameter maps.
     * @returns {SearchCriteriaFieldFilter | null} The built search criteria field filter object.
     * @protected
     */
    protected buildSearchCriteriaFieldFilter (
        fieldFilterName: string,
        fieldFilterFieldType: string,
        queryParams: Params,
        fieldFilterKey: string,
        queryFilterOperatorKeyTemplate: string,
        queryFilterOperatorParamsMap: NestedGenericMap<string>
    ): SearchCriteriaFieldFilter | null {
        const searchCriteriaFieldFilter = {
            field: fieldFilterName,
            fieldType: fieldFilterFieldType,
            operator: '=',
            values: []
        } as SearchCriteriaFieldFilter;

        if (isEmpty(queryFilterOperatorKeyTemplate) || isEmpty(queryFilterOperatorParamsMap)) {
            const fieldFilterValue = this.getQueryParamValue(
                fieldFilterKey,
                fieldFilterKey,
                queryParams
            );
            if (isEmpty(fieldFilterValue) && !isEmptyString(fieldFilterValue)) {
                return null;
            }

            const values = isArray(fieldFilterValue)
                ? fieldFilterValue
                : [fieldFilterValue];

            searchCriteriaFieldFilter.values = values;
            searchCriteriaFieldFilter.target = values[0];

            return this.checkDateSpecialsOrReturn(
                searchCriteriaFieldFilter,
                searchCriteriaFieldFilter.target
            );
        }

        const queryFilterOperatorKey = this.getQueryParamValue(
            queryFilterOperatorKeyTemplate,
            fieldFilterKey,
            queryParams,
            { forceSingleString: true }
        ) as string;
        const queryFilterOperatorParams = (
            queryFilterOperatorParamsMap[queryFilterOperatorKey] ??
            Object
                .values(queryFilterOperatorParamsMap)
                .reduce((prev, curr) => (
                    { ...prev, ...curr }
                ), {})
            ?? {}
        ) as GenericMap<string>;
        if (isEmpty(queryFilterOperatorParams)) {
            return null;
        }

        let returnEmpty = true;
        searchCriteriaFieldFilter.operator = queryFilterOperatorKey;
        Object.entries(queryFilterOperatorParams)
            .filter(([_, searchCriteriaPropertyKey]) => (
                typeof searchCriteriaPropertyKey === 'string'
                && this.allowedProperties.includes(searchCriteriaPropertyKey)
            ))
            .forEach(([searchCriteriaPropertyValueTemplate, searchCriteriaPropertyKey]) => {
                const rawSearchCriteriaPropertyValue = this.getQueryParamValue(
                    searchCriteriaPropertyValueTemplate,
                    fieldFilterKey,
                    queryParams
                );

                if (isEmpty(rawSearchCriteriaPropertyValue)) {
                    return;
                }
                returnEmpty = false;

                let searchCriteriaPropertyValue = rawSearchCriteriaPropertyValue;

                if (searchCriteriaPropertyKey === 'values') {
                    if (!isArray(searchCriteriaPropertyValue)) {
                        searchCriteriaPropertyValue = [searchCriteriaPropertyValue];
                    }

                    searchCriteriaFieldFilter['target'] = searchCriteriaPropertyValue[0];
                } else if (searchCriteriaPropertyKey === 'target') {
                    if (isArray(searchCriteriaPropertyValue)) {
                        searchCriteriaPropertyValue = searchCriteriaPropertyValue[0];
                    }

                    searchCriteriaFieldFilter['values'] = [searchCriteriaPropertyValue] as string[];
                }

                searchCriteriaFieldFilter[searchCriteriaPropertyKey] = searchCriteriaPropertyValue;

                if (!isArray(searchCriteriaPropertyValue)) {
                    this.checkDateSpecialsOrReturn(
                        searchCriteriaFieldFilter,
                        searchCriteriaPropertyValue,
                        {
                            operator: queryFilterOperatorKey,
                            key: searchCriteriaPropertyKey
                        }
                    );
                }
            });

        return !returnEmpty ? this.checkForMissingOperator(searchCriteriaFieldFilter) : null;
    }

    /**
     * Retrieves the value of a query parameter based on the provided queryParamKeyTemplate,
     * fieldFilterKey, and queryParams.
     *
     * @param {string} queryParamKeyTemplate - The template for the query parameter key, with "{field}" as a placeholder for fieldFilterKey.
     * @param {string} fieldFilterKey - The field filter key used to replace the "{field}" placeholder in queryParamKeyTemplate.
     * @param {Params} queryParams - The object containing the query parameters.
     * @param {object} options - Optional parameters to customize the behavior of the method.
     * @param {boolean} options.forceSingleString - Flag indicating whether the result should always be a single string value.
     * @returns {string|string[]} - The value of the query parameter. If forceSingleString is false, it will be either a string or an array of strings.
     * @protected
     */
    protected getQueryParamValue (
        queryParamKeyTemplate: string,
        fieldFilterKey: string,
        queryParams: Params,
        { forceSingleString = false } = {}
    ): string | string[] | null {
        const queryParamKey = queryParamKeyTemplate.replace(
            '{field}',
            fieldFilterKey
        ) ?? '';

        let queryParamValue = queryParams[queryParamKey];

        if (!queryParamValue) {
            return null;
        }

        if (isArray(queryParamValue)) {
            queryParamValue = queryParamValue.map(this.transform);
        } else {
            queryParamValue = this.transform(queryParamValue);
        }

        if (forceSingleString && isArray(queryParamValue)) {
            return queryParamValue[0] ?? '';
        }

        return queryParamValue;
    }

    /**
     * Cleans the query parameter key by removing the '[]' brackets if present.
     *
     * @returns {string} - The cleaned query parameter key.
     * @protected
     * @param queryParam
     */
    protected cleanQueryParam (queryParam: [string, string | string[]]): [string, string | string[]] {
        let [queryParamKey, queryParamVal] = queryParam;

        const queryParamKeyReversed = queryParamKey.split('').reverse().join('');
        if (queryParamKeyReversed.indexOf('][') === 0 && typeof queryParamVal === 'string') {
            queryParamKey = queryParamKey.replace('[]', '');
            queryParamVal = queryParamVal.split(',');
        }

        return [queryParamKey, queryParamVal];
    }

    /**
     * Checks if given fieldFilterValue matches MONTH_YEAR_REGEX or yearRegex and returns
     * overridesSearchCriteriaFieldFilter if true, else returns searchCriteriaFieldFilter.
     *
     * @param {SearchCriteriaFieldFilter} searchCriteriaFieldFilter - The search criteria field filter.
     * @param {string} fieldFilterValue - The field filter value.
     * @param {Object} options - The options object.
     * @param {string} [options.operator='='] - The range option.
     * @param {string} [options.key='target'] - The key option.
     * @returns {SearchCriteriaFieldFilter} - The updated search criteria field filter.
     * @protected
     */
    protected checkDateSpecialsOrReturn (
        searchCriteriaFieldFilter: SearchCriteriaFieldFilter,
        fieldFilterValue: string,
        { operator = '=', key = 'target' }: { operator?: string, key?: string } = {}
    ): SearchCriteriaFieldFilter {
        if (fieldFilterValue.match(MONTH_YEAR_REGEX)) {
            return this.overridesSearchCriteriaFieldFilter(
                searchCriteriaFieldFilter,
                fieldFilterValue,
                { type: 'month', operator, key }
            );
        }

        if (fieldFilterValue.match(MONTH_REGEX)) {
            return this.overridesSearchCriteriaFieldFilter(
                searchCriteriaFieldFilter,
                fieldFilterValue,
                { type: 'year', operator, key }
            );
        }

        return searchCriteriaFieldFilter;
    }

    /**
     * Overrides the search criteria field filter based on the provided parameters.
     *
     * @param {SearchCriteriaFieldFilter} searchCriteriaFieldFilter - The original search criteria field filter.
     * @param {string} fieldFilterValue - The value of the field filter.
     * @param {Object} options - The options for overriding the field filter.
     * @param {string} options.type - The type of the field filter.
     * @param {string} [options.operator='equal'] - The operator for the field filter.
     * @param {string} [options.key='target'] - The key for the field filter.
     * @protected
     * @returns {SearchCriteriaFieldFilter} - The overridden search criteria field filter.
     */
    protected overridesSearchCriteriaFieldFilter (
        searchCriteriaFieldFilter: SearchCriteriaFieldFilter,
        fieldFilterValue: string,
        { type = '', operator = 'equal', key = 'target' }: {
            type: string,
            operator?: string,
            key?: string
        }
    ): SearchCriteriaFieldFilter {
        let plusObject;
        let fmt;
        switch (type) {
            case 'year':
                plusObject = { year: 1 };
                fmt = 'yyyy';
                break;
            case 'month':
                plusObject = { month: 1 };
                fmt = 'yyyy-MM';
                break;
            default:
                return searchCriteriaFieldFilter;
        }

        const start = DateTime.fromFormat(fieldFilterValue, fmt);
        const end = start.plus(plusObject).minus({ day: 1 });

        if (key !== 'target') {
            switch (key) {
                case 'start':
                    searchCriteriaFieldFilter.start = start.toFormat('yyyy-MM-dd');
                    break;
                case 'end':
                    searchCriteriaFieldFilter.end = end.toFormat('yyyy-MM-dd');
                    break;
            }
            return searchCriteriaFieldFilter;
        }

        searchCriteriaFieldFilter.operator = operator;
        switch (operator) {
            case 'greater_than':
            case 'greater_than_equals':
                searchCriteriaFieldFilter.start = start.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.target = searchCriteriaFieldFilter.start;
                searchCriteriaFieldFilter.values = [searchCriteriaFieldFilter.target];
                break;
            case 'less_than':
            case 'less_than_equals':
                searchCriteriaFieldFilter.end = end.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.target = searchCriteriaFieldFilter.end;
                searchCriteriaFieldFilter.values = [searchCriteriaFieldFilter.target];
                break;
            case 'not_equal':
                searchCriteriaFieldFilter.start = start.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.end = end.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.target = fieldFilterValue;
                searchCriteriaFieldFilter.values = [fieldFilterValue];
                break;
            case 'equal':
            case 'between':
            default:
                searchCriteriaFieldFilter.operator = 'between';
                searchCriteriaFieldFilter.start = start.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.end = end.toFormat('yyyy-MM-dd');
                searchCriteriaFieldFilter.target = '';
                searchCriteriaFieldFilter.values = [];
                break;
        }

        return searchCriteriaFieldFilter;
    }

    /**
     * Converts the given value to the internal format based on the specified type.
     *
     * @param {string} type - The type of value to convert to.
     * @param {string} value - The value to convert.
     * @return {string} - The converted value in the internal format.
     * @protected
     */
    protected toInternalFormat (type: string, value: string): string {
        if (value.match(MONTH_REGEX) || value.match(MONTH_YEAR_REGEX)) {
            return value;
        }
        return this.dataTypeFormatter.toInternalFormat(type, value);
    };

    /**
     * Transforms the given value from url to a value understandable by backend.
     *
     * @param {any} value - The value to be transformed.
     * @protected
     * @return {string} The transformed value.
     */
    protected transform (value: any): string {
        switch (value) {
            case '':
                return '__SuiteCRMEmptyString__';
            default:
                return value;
        }
    }

    protected checkForMissingOperator (searchCriteriaFieldFilter: SearchCriteriaFieldFilter): SearchCriteriaFieldFilter {
        if (
            !isEmpty(searchCriteriaFieldFilter.start)
            && !isEmpty(searchCriteriaFieldFilter.end)
        ) {
            searchCriteriaFieldFilter.operator = 'between';
        }

        return searchCriteriaFieldFilter;
    }
}
