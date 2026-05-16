<?php

namespace BarrierefreiSpace\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('barrierefreiSpaceDonationWidgetListTypeToCTypeUpdate')]
final class DonationWidgetListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'ALT Text Gen: Migrate Donation Widget to CType';
    }

    public function getDescription(): string
    {
        return 'Migrates legacy DonationWidget list_type values to barrierefreispace_donationwidget CType.';
    }

    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'alttextgen_donationwidget' => 'barrierefreispace_donationwidget',
            'barrierefreispace_donationwidget' => 'barrierefreispace_donationwidget',
            'barrierefrei_space_donationwidget' => 'barrierefreispace_donationwidget',
        ];
    }
}
