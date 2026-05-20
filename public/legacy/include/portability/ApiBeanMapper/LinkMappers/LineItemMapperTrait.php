<?php

trait LineItemMapperTrait
{

    /**
     * Get relate field definition
     * @param SugarBean $bean
     * @param string $name
     * @return array|mixed
     */
    protected function getDefinition(SugarBean $bean, string $name)
    {
        return $bean->field_defs[$name] ?? [];
    }

    /**
     * @param SugarBean $bean
     * @param array $definition
     * @return SugarBean[]
     */
    protected function getItemBeans(SugarBean $bean, array $definition): array
    {
        $relationship = $definition['relationship'] ?? $definition['link'] ?? false;
        $linkName = $definition['link'] ?? $definition['name'] ?? false;

        $bean->load_relationship($relationship);
        /** @var Link2 $link */
        $link = $bean->$linkName;

        if (empty($link)) {
            return [];
        }

        return $link->getBeans();
    }


    /**
     * @param SugarBean $itemBean
     * @return array
     */
    protected function mapItem(SugarBean $itemBean): array
    {
        return (new ApiBeanMapper())->toApi($itemBean);
    }

    /**
     * @param array $itemBeans
     * @param array $container
     * @param string $newName
     * @return array
     */
    protected function mapBeansToApi(array $itemBeans, array $container, string $newName): array
    {
        $container[$newName] = [];
        foreach ($itemBeans as $itemBean) {
            $attributes = $this->mapItem($itemBean);
            $itemModule = $itemBean->module_name ?? '';
            $record = [
                'id' => $attributes['id'],
                'module' => $itemModule,
                'attributes' => $attributes
            ];

            $container[$newName][] = $record;
        }
        return $container;
    }

}
