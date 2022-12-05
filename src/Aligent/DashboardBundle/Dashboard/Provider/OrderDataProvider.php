<?php
/**
 * @category  Aligent
 * @package
 * @author    Greg Ziborov <greg.ziborov@aligent.com.au>
 * @copyright 2021 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\DashboardBundle\Dashboard\Provider;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;
use Exception;
use Oro\Bundle\ChartBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provide functionality to get order data
 */
class OrderDataProvider
{

    protected ManagerRegistry $registry;
    protected AclHelper $aclHelper;
    protected ConfigProvider $configProvider;
    protected DateTimeFormatterInterface $dateTimeFormatter;
    protected DateHelper $dateHelper;

    public function __construct(
        ManagerRegistry $registry,
        AclHelper $aclHelper,
        ConfigProvider $configProvider,
        DateTimeFormatterInterface $dateTimeFormatter,
        DateHelper $dateHelper
    ) {
        $this->registry          = $registry;
        $this->aclHelper         = $aclHelper;
        $this->configProvider    = $configProvider;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->dateHelper        = $dateHelper;
    }

    /**
     * @param ChartViewBuilder $viewBuilder
     * @param array<string, mixed> $dateRange
     *
     * @return ChartView
     * @throws Exception
     */
    public function getOrdersOverTimeChartView(ChartViewBuilder $viewBuilder, array $dateRange): ChartView
    {
        /* @var $from DateTime */
        /* @var $to DateTime */
        list($from, $to) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        if ($from === null && $to === null) {
            $from = new DateTime(DateHelper::MIN_DATE, new \DateTimeZone('UTC'));
            $to   = new DateTime('now', new \DateTimeZone('UTC'));
        }
        $result = $this->getOrdersOverTime($this->aclHelper, $this->dateHelper, $from, $to);
        $items  = $this->dateHelper->convertToCurrentPeriod($from, $to, $result, 'cnt', 'count');

        $previousFrom   = $this->createPreviousFrom($from, $to);
        $previousResult = $this->getOrdersOverTime(
            $this->aclHelper,
            $this->dateHelper,
            $previousFrom,
            $from
        );
        $previousItems  = $this->dateHelper->combinePreviousDataWithCurrentPeriod(
            $previousFrom,
            $from,
            $previousResult,
            'cnt',
            'count'
        );

        $chartType = $this->dateHelper->getFormatStrings($from, $to)['viewType'];
        $data      = [
            $this->createPeriodLabel($previousFrom, $from) => $previousItems,
            $this->createPeriodLabel($from, $to)           => $items,
        ];

        return $this->createPeriodChartView($viewBuilder, 'orders_over_time_chart', $chartType, $data);
    }

    /**
     * @param ChartViewBuilder $viewBuilder
     * @param array<string, mixed> $dateRange
     *
     * @return ChartView
     * @throws Exception
     */
    public function getRevenueOverTimeChartView(ChartViewBuilder $viewBuilder, array $dateRange): ChartView
    {
        /* @var $from DateTime */
        /* @var $to DateTime */
        list($from, $to) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        if ($from === null && $to === null) {
            $from = new DateTime(DateHelper::MIN_DATE, new \DateTimeZone('UTC'));
            $to   = new DateTime('now', new \DateTimeZone('UTC'));
        }
        $result = $this->getRevenueOverTime($this->aclHelper, $this->dateHelper, $from, $to);
        $items  = $this->dateHelper->convertToCurrentPeriod($from, $to, $result, 'amount', 'amount');

        $previousFrom   = $this->createPreviousFrom($from, $to);
        $previousResult = $this->getRevenueOverTime(
            $this->aclHelper,
            $this->dateHelper,
            $previousFrom,
            $from
        );
        $previousItems  = $this->dateHelper->combinePreviousDataWithCurrentPeriod(
            $previousFrom,
            $from,
            $previousResult,
            'amount',
            'amount'
        );

        $chartType = $this->dateHelper->getFormatStrings($from, $to)['viewType'];
        $data      = [
            $this->createPeriodLabel($previousFrom, $from) => $previousItems,
            $this->createPeriodLabel($from, $to)           => $items,
        ];

        return $this->createPeriodChartView($viewBuilder, 'revenue_over_time_chart', $chartType, $data);
    }

    /**
     * @param AclHelper $aclHelper
     * @param DateHelper $dateHelper
     * @param DateTime $from
     * @param DateTime|null $to
     *
     * @return array<object>
     */
    public function getOrdersOverTime(
        AclHelper  $aclHelper,
        DateHelper $dateHelper,
        DateTime   $from,
        DateTime   $to = null
    ): array {
        $from = clone $from;
        $to   = clone $to;

        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->registry->getRepository(Order::class);
        $qb = $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.id) AS cnt');

        $dateHelper->addDatePartsSelect($from, $to, $qb, 'o.createdAt');

        if ($to) {
            $qb->andWhere($qb->expr()->between('o.createdAt', ':from', ':to'))
                ->setParameter('to', $to, Types::DATETIME_MUTABLE);
        } else {
            $qb->andWhere('o.createdAt > :from');
        }
        $qb->setParameter('from', $from, Types::DATETIME_MUTABLE);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param AclHelper      $aclHelper
     * @param DateHelper     $dateHelper
     * @param DateTime $from
     * @param DateTime|null $to
     *
     * @return array<object>
     */
    public function getRevenueOverTime(
        AclHelper  $aclHelper,
        DateHelper $dateHelper,
        DateTime   $from,
        DateTime   $to = null
    ): array {
        $from = clone $from;
        $to   = clone $to;

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->registry->getRepository(Order::class);
        $qb = $orderRepository->createQueryBuilder('o')
            ->select('SUM(
                    CASE WHEN o.subtotalValue IS NOT NULL THEN o.subtotalValue ELSE 0 END -
                    CASE WHEN o.totalDiscountsAmount IS NOT NULL THEN ABS(o.totalDiscountsAmount) ELSE 0 END
                ) AS amount');

        $dateHelper->addDatePartsSelect($from, $to, $qb, 'o.createdAt');

        if ($to) {
            $qb->andWhere($qb->expr()->between('o.createdAt', ':from', ':to'))
                ->setParameter('to', $to, Types::DATETIME_MUTABLE);
        } else {
            $qb->andWhere('o.createdAt > :from');
        }
        $qb->setParameter('from', $from, Types::DATETIME_MUTABLE);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param ChartViewBuilder $viewBuilder
     * @param string $chart
     * @param string $type
     * @param array<string, array<mixed>> $data
     *
     * @return ChartView
     * @throws InvalidConfigurationException
     */
    protected function createPeriodChartView(
        ChartViewBuilder $viewBuilder,
        string $chart,
        string $type,
        array $data
    ): ChartView {
        $chartOptions = array_merge_recursive(
            ['name' => 'multiline_chart'],
            $this->configProvider->getChartConfig($chart)
        );
        $chartOptions['data_schema']['label']['type']  = $type;
        $chartOptions['data_schema']['label']['label'] =
            sprintf(
                'oro.dashboard.chart.%s.label',
                $type
            );

        return $viewBuilder->setOptions($chartOptions)
            ->setArrayData($data)
            ->getView();
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return DateTime
     */
    protected function createPreviousFrom(DateTime $from, DateTime $to): DateTime
    {
        $diff         = $to->getTimestamp() - $from->getTimestamp();
        $previousFrom = clone $from;
        $previousFrom->setTimestamp($previousFrom->getTimestamp() - $diff);

        return $previousFrom;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return string
     */
    protected function createPeriodLabel(DateTime $from, DateTime $to): string
    {
        return sprintf(
            '%s - %s',
            $this->dateTimeFormatter->formatDate($from),
            $this->dateTimeFormatter->formatDate($to)
        );
    }
}
