<?php

namespace BarrierefreiSpace\FormEngine\Wizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AltTextGeneratorWizard extends AbstractNode
{
    /**
     * Render a "Generate ALT" button next to sys_file_reference.alternative and load the JS module.
     *
     * Returns
     *     array: FormEngine render result array.
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $record = is_array($this->data['databaseRow'] ?? null) ? $this->data['databaseRow'] : [];
        $fileReferenceUid = (int) ($record['uid'] ?? 0);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@barrierefrei-space/alt-text-generator.js');

        $result['html'] = sprintf(
            '<button type="button" class="btn btn-default btn-sm t3js-alttextgen" data-fileref-uid="%d" data-label-generate="%s" data-label-generating="%s" title="%s">%s</button>',
            $fileReferenceUid,
            htmlspecialchars($this->translate('button.generate')),
            htmlspecialchars($this->translate('button.generating')),
            htmlspecialchars($this->translate('button.generate')),
            htmlspecialchars($this->translate('button.generate'))
        );

        return $result;
    }

    /**
     * Fetch a localized label (default English) for backend button texts.
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
