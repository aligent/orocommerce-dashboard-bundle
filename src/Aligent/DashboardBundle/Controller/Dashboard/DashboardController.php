<?php
/**
 * @category  Aligent
 * @package
 * @author    Greg Ziborov <greg.ziborov@aligent.com.au>
 * @copyright 2021 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\DashboardBundle\Controller\Dashboard;

use Aligent\DashboardBundle\Dashboard\Provider\OrderDataProvider;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds action which are responsible for rendering Aligent dashboard chart widgets
 */
class DashboardController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            WidgetConfigs::class,
//            WorkflowAwareManager::class,
            TranslatorInterface::class,
            AclHelper::class,
            ChartViewBuilder::class,
            OrderDataProvider::class,
            DateHelper::class,
        ]);
    }

    /**
     * @Route(
     *      "/aligent_dashboard_revenue_over_time_chart",
     *      name="aligent_dashboard_revenue_over_time_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("AligentDashboardBundle:Dashboard:revenueOverTimeChart.html.twig")
     *
     * @return array<string, mixed>
     * @throws InvalidConfigurationException
     */
    public function revenueOverTimeAction(): array
    {
        $widgetAttributes  = $this->get(WidgetConfigs::class);
        $orderDataProvider = $this->get(OrderDataProvider::class);
        $chartViewBuilder  = $this->get(ChartViewBuilder::class);

        $data              = $widgetAttributes->getWidgetAttributesForTwig('revenue_over_time_chart');
        $data['chartView'] = $orderDataProvider->getRevenueOverTimeChartView(
            $chartViewBuilder,
            $widgetAttributes
                ->getWidgetOptions()
                ->get('dateRange')
        );

        return $data;
    }

    /**
     * @Route(
     *      "/aligent_dashboard_orders_over_time_chart",
     *      name="aligent_dashboard_orders_over_time_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("AligentDashboardBundle:Dashboard:ordersOverTimeChart.html.twig")
     *
     * @return array<string, mixed>
     * @throws InvalidConfigurationException
     */
    public function ordersOverTimeAction(): array
    {
        $widgetAttributes  = $this->get(WidgetConfigs::class);
        $orderDataProvider = $this->get(OrderDataProvider::class);
        $chartViewBuilder  = $this->get(ChartViewBuilder::class);

        $data              = $widgetAttributes->getWidgetAttributesForTwig('orders_over_time_chart');
        $data['chartView'] = $orderDataProvider->getOrdersOverTimeChartView(
            $chartViewBuilder,
            $widgetAttributes
                ->getWidgetOptions()
                ->get('dateRange')
        );

        return $data;
    }
}
