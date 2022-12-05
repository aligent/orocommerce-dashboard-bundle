<?php


namespace Aligent\DashboardBundle\Dashboard\Converter;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;

/**
 * Converts a date range configuration of a dashboard widget
 * to a representation that can be used to filter data and vise versa.
 */
class FilterDateRangeConverterDecorator extends FilterDateRangeConverter
{
    /**
     * Borrowed from Oro Core with couple of changes mentioned in the comments
     * @param array<string, mixed> $value
     * @param boolean $cretePreviousPeriod
     * @return array<string, mixed>
     */
    protected function processValueTypes(array $value, $cretePreviousPeriod): array
    {
        $start = $end = $part = $prevStart = $prevEnd = null;
        $type = $value['type'] ?? AbstractDateFilterType::TYPE_BETWEEN;
        if (array_key_exists($value['type'], static::$valueTypesStartVarsMap)) {
            /** @var Carbon $start */
            $start = $this->dateCompiler->compile(
                sprintf('{{%s}}', static::$valueTypesStartVarsMap[$value['type']]['var_start'])
            );
            $end = clone $start;
            $modify = static::$valueTypesStartVarsMap[$value['type']]['modify_end'];
            if ($modify) {
                $end->modify($modify);
            }
            $start->setTime(0, 0, 0);
            /** $end of period calculation changed from the first day of next month to the last day of current month */
            $end->setTime(23, 59, 59);
            if ($cretePreviousPeriod) {
                $prevStart = clone $start;
                $prevModify = static::$valueTypesStartVarsMap[$value['type']]['modify_previous_start'];
                if ($prevModify) {
                    $prevStart->modify($prevModify);
                }
                $prevEnd = clone $prevStart;
                if ($modify) {
                    $prevEnd->modify($modify);
                }
                $prevStart->setTime(0, 0, 0);
                /** $end of period calculation changed from the first day of next month to the last day of current month */
                $prevEnd->setTime(23, 59, 59);
            }
        }

        if ($value['type'] === AbstractDateFilterType::TYPE_ALL_TIME) {
            $part = DateModifierInterface::PART_ALL_TIME;
        }

        return [
            'start' => $start,
            'end'   =>  $end,
            'type'  => $type,
            'part'  => $part,
            'prev_start' => $prevStart,
            'prev_end'   => $prevEnd
        ];
    }
}
