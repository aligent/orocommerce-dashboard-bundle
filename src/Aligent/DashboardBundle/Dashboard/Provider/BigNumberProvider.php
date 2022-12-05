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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DashboardBundle\Provider\BigNumber\BigNumberDateHelper;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Calculates various metrics for Order stats widget.
 */
class BigNumberProvider
{
    use DateFilterTrait;

    protected ManagerRegistry $doctrine;
    protected AclHelper $aclHelper;
    protected BigNumberDateHelper $dateHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        BigNumberDateHelper $dateHelper
    ) {
        $this->doctrine   = $doctrine;
        $this->aclHelper  = $aclHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param array<string, mixed> $dateRange
     *
     * @return float
     * @throws NonUniqueResultException
     */
    public function getRevenueValues(array $dateRange): float
    {
        list($start, $end) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        $select = 'SUM(
             CASE WHEN o.subtotalValue IS NOT NULL THEN o.subtotalValue ELSE 0 END -
             CASE WHEN o.totalDiscountsAmount IS NOT NULL THEN ABS(o.totalDiscountsAmount) ELSE 0 END
             ) as val';

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->doctrine->getRepository(Order::class);
        $qb = $orderRepository->createQueryBuilder('o');
        $qb->select($select);
        $this->applyDateFiltering($qb, 'o.createdAt', $start, $end);
        $value = $this->aclHelper->apply($qb)->setMaxResults(1)->getOneOrNullResult();

        return $value['val'] ? : 0;
    }

    /**
     * @param array<string, mixed> $dateRange
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function getOrdersNumberValues(array $dateRange): int
    {
        list($start, $end) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->doctrine->getRepository(Order::class);
        $qb = $orderRepository->createQueryBuilder('o');
        $qb->select('COUNT(o.id) as val');
        $this->applyDateFiltering($qb, 'o.createdAt', $start, $end);
        $value = $this->aclHelper->apply($qb)->setMaxResults(1)->getOneOrNullResult();

        return $value['val'] ? : 0;
    }

    /**
     * @param array<string, mixed> $dateRange
     *
     * @return float|int
     * @throws NonUniqueResultException
     */
    public function getDiscountedOrdersPercentValues(array $dateRange): float|int
    {
        list($start, $end) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->doctrine->getRepository(Order::class);
        $qb = $orderRepository->createQueryBuilder('o');
        $qb->select(
            'COUNT(o.id) as allOrders',
            'SUM(
             CASE WHEN (o.totalDiscountsAmount IS NOT NULL AND o.totalDiscountsAmount <> 0) THEN 1 ELSE 0 END
             ) as discounted'
        );
        $this->applyDateFiltering($qb, 'o.createdAt', $start, $end);
        $value = $this->aclHelper->apply($qb)->setMaxResults(1)->getOneOrNullResult();

        return $value['allOrders'] ? $value['discounted'] / $value['allOrders'] : 0;
    }

    /**
     * @param array<string, mixed> $dateRange
     *
     * @return float|int
     * @throws NonUniqueResultException
     */
    public function getAverageOrdersValues(array $dateRange): float|int
    {
        list($start, $end) = $this->dateHelper->getPeriod($dateRange, Order::class, 'createdAt');
        $select = 'SUM(
             CASE WHEN o.subtotalValue IS NOT NULL THEN o.subtotalValue ELSE 0 END -
             CASE WHEN o.totalDiscountsAmount IS NOT NULL THEN ABS(o.totalDiscountsAmount) ELSE 0 END
             ) as revenue,
             count(o.id) as ordersCount';
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->doctrine->getRepository(Order::class);
        $qb = $orderRepository->createQueryBuilder('o');
        $qb->select($select);
        $this->applyDateFiltering($qb, 'o.createdAt', $start, $end);
        $value = $this->aclHelper->apply($qb)->setMaxResults(1)->getOneOrNullResult();

        return $value['ordersCount'] ? round($value['revenue'] / $value['ordersCount'], 2) : 0;
    }
}
