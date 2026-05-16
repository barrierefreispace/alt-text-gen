<?php

namespace BarrierefreiSpace\FormEngine\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

final class AltTextGeneratorControl extends AbstractNode
{
    /**
     * Render a fieldControl (button/icon) on the right side of the ALT field to trigger ALT text generation.
     *
     * Returns fieldControl result array (must contain iconIdentifier/title/linkAttributes).
     */
    public function render(): array
    {
        $record = is_array($this->data['databaseRow'] ?? null) ? $this->data['databaseRow'] : [];
        $fileReferenceUid = (int) ($record['uid'] ?? 0);

        $parameterArray = is_array($this->data['parameterArray'] ?? null) ? $this->data['parameterArray'] : [];
        $itemFormElName = (string) ($parameterArray['itemFormElName'] ?? '');
        $itemFormElId = (string) ($parameterArray['itemFormElID'] ?? ($parameterArray['itemFormElId'] ?? ''));
        $persistedStyle = strtolower(trim((string) ($record['tx_barrierefrei_space_style'] ?? 'formal')));
        $persistedSeoKeywords = trim((string) ($record['tx_barrierefrei_space_seo_keywords'] ?? ''));

        $labelGenerate = $this->translate('button.generate');
        $labelGenerating = $this->translate('button.generating');

        return [
            'iconIdentifier' => 'tx-barrierefrei-space-wand',
            'title' => $labelGenerate,
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@barrierefrei-space/alt-text-generator.js'),
            ],
            'linkAttributes' => [
                'class' => 't3js-alttextgen',
                'data-fileref-uid' => (string) $fileReferenceUid,
                'data-formel-name' => $itemFormElName,
                'data-formel-id' => $itemFormElId,
                'data-label-generate' => $labelGenerate,
                'data-label-generating' => $labelGenerating,
                'data-persisted-style' => $persistedStyle,
                'data-persisted-seo-keywords' => $persistedSeoKeywords,
                'aria-label' => $labelGenerate,
            ],
        ];
    }

    /**
     * Fetch a localized label (default English) for tooltip texts.
     *
     * @param string $key Language key.
     * @return string Translated text.
     */
    private function translate(string $key): string
    {
        $lang = $GLOBALS['LANG'] ?? null;
        if (!is_object($lang) || !method_exists($lang, 'sL')) {
            return $key;
        }

        $label = (string) $lang->sL('LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:' . $key);
        return $label !== '' ? $label : $key;
    }

}
