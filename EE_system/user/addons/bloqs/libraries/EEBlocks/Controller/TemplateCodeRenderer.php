<?php

namespace EEBlocks\Controller;

use EEBlocks\Database\Adapter;
use EEBlocks\Model\BlockDefinition;

class TemplateCodeRenderer
{
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var array
     */
    private $installedFieldtypes = [];

    /**
     * @param Adapter $adapter
     * @param array $installedFieldtypes
     */
    public function __construct(Adapter $adapter, $installedFieldtypes = [])
    {
        $this->adapter = $adapter;
        $this->installedFieldtypes = $installedFieldtypes;
    }

    public function renderBlockTemplate($blockDefinitionId)
    {
        $blockDefinition = $this->adapter->getBlockDefinitionById($blockDefinitionId);
        $blocks = $this->getBlocks([$blockDefinition]);

        return $this->renderBlocks($blocks, [], 1);
    }

    /**
     * @param string $fieldName
     * @param int $fieldId
     * @param array $includeBlocks
     * @param bool $isNestable
     * @return string
     */
    public function renderFieldTemplate($fieldName = '', $fieldId, $includeBlocks = [], $isNestable = false)
    {
        $blockDefinitions = $this->adapter->getBlockDefinitionsForField($fieldId);
        $blocks = $this->getBlocks($blockDefinitions);

        $output = LD . $fieldName . RD . PHP_EOL;
        $output .= $this->renderBlocks($blocks, $includeBlocks, 1, $isNestable);
        $output .= LD . '/' . $fieldName . RD;

        return $output;
    }

    /**
     * @param array $blockDefinitions
     */
    public function getBlocks($blockDefinitions = [])
    {
        $blocks = [];
        $installedFieldtypes = $this->installedFieldtypes;

        /** @var BlockDefinition $definition */
        foreach ($blockDefinitions as $definition) {
            $blocks[$definition->shortname] = [];
            foreach ($definition->getAtomDefinitions() as $atomName => $atom) {
                $type = $atom->type;
                $path = $installedFieldtypes[$type]['path'] . $installedFieldtypes[$type]['file'];
                if (!file_exists($path)) {
                    continue;
                }
                require_once $path;
                $fieldtype = new $installedFieldtypes[$type]['class'];
                $hasArrayData = (isset($fieldtype->has_array_data) && $fieldtype->has_array_data);
                $blocks[$definition->getShortName()][$atomName] = [
                    'isPair' => ($hasArrayData || $type === 'relationship'),
                    'type' => $type,
                ];
            }
        }

        return $blocks;
    }

    /**
     * @param array $blocks
     * @param array $includeBlocks
     * @param int $indentMultiplier
     * @param bool $isNestable
     * @return string
     */
    private function renderBlocks($blocks = [], $includeBlocks = [], $indentMultiplier = 1, $isNestable = false)
    {
        $output = '';
        $indent = '    ';

        foreach ($blocks as $blockName => $atoms) {
            if (!empty($includeBlocks) && !in_array($blockName, $includeBlocks)) {
                continue;
            }

            $output .= str_repeat($indent, $indentMultiplier) . LD . $blockName . RD . PHP_EOL;

            if ($isNestable) {
                $output .= str_repeat($indent, $indentMultiplier) . '&lt;div data-block-name="' . $blockName . '"&gt;' . PHP_EOL;
            }

            foreach ($atoms as $atomName => $atom) {
                $output .= str_repeat($indent, $indentMultiplier * 2) . LD . $atomName . RD . PHP_EOL;
                if ($atom['isPair']) {
                    $output .= str_repeat($indent, $indentMultiplier * 2) . LD . '/' . $atomName . RD . PHP_EOL;
                }
            }

            if ($isNestable) {
                $output .= str_repeat($indent, $indentMultiplier) . LD . 'close:' . $blockName . RD . PHP_EOL;
                $output .= str_repeat($indent, $indentMultiplier) . '&lt;/div&gt;' . PHP_EOL;
                $output .= str_repeat($indent, $indentMultiplier) . LD . '/close:' . $blockName . RD . PHP_EOL;
            }

            $output .= str_repeat($indent, $indentMultiplier) . LD . '/' . $blockName . RD . PHP_EOL . PHP_EOL;
        }

        return $output;
    }

}
