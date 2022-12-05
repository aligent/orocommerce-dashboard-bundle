<?php
/**
 * @category  Aligent
 * @package
 * @author    Greg Ziborov <greg.ziborov@aligent.com.au>
 * @copyright 2021 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */


namespace Aligent\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\AbstractDashboardFixture;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        // we need admin user as a dashboard owner
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {

        // find the dashboard in oro
        $dashboard = $this->findAdminDashboardModel(
            $manager,      // pass ObjectManager
            'sample_dashboard' // dashboard name
        );

        // add the dashboard if it doesn't exist in oro
        if (!$dashboard) {
            // create new dashboard
            $dashboard = $this->createAdminDashboardModel(
                $manager,      // pass ObjectManager
                'sample_dashboard' // dashboard name
            );

            $dashboard
                // if user doesn't have active dashboard this one will be used
                ->setIsDefault(false)

                // dashboard label
                ->setLabel(
                    $this->container->get('translator')->trans('aligent.dashboard.title.sample_dashboard')
                )

                // add widgets one by one
                ->addWidget(
                    $this->createWidgetModel(
                        'popular_products_datagrid_widget',  // widget name from yml configuration
                        [
                            0, // column, starting from left
                            10 // position, starting from top
                        ]
                    )
                )

                ->addWidget(
                    $this->createWidgetModel(
                        'revenue_over_time_chart_widget',  // widget name from yml configuration
                        [
                            1, // column, starting from left
                            20 // position, starting from top
                        ]
                    )
                )

                ->addWidget(
                    $this->createWidgetModel(
                        'orders_big_numbers_widget',  // widget name from yml configuration
                        [
                            1, // column, starting from left
                            30 // position, starting from top
                        ]
                    )
                );

            $manager->flush();
        }
    }
}
