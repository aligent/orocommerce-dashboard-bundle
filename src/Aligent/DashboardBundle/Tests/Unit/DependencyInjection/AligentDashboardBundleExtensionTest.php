<?php
/**
 * @category  Aligent
 * @author    Bruno Pasqualini <bruno.pasqualini@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @link      http://www.aligent.com.au/
 */

namespace Aligent\DashboardBundle\Tests\Unit\DependencyInjection;

use Aligent\DashboardBundle\DependencyInjection\AligentDashboardExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class AligentDashboardBundleExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new AligentDashboardExtension());

        // Services
        $expectedDefinitions = [
            'Aligent\DashboardBundle\Dashboard\Provider\BigNumberProvider',
            'Aligent\DashboardBundle\Dashboard\Provider\OrderDataProvider',
            'Aligent\DashboardBundle\Dashboard\Converter\FilterDateRangeConverterDecorator',
            'Aligent\DashboardBundle\Controller\Dashboard\DashboardController',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
