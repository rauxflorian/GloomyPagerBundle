<?php

namespace Gloomy\PagerBundle\Pager\Wrapper;

use Gloomy\PagerBundle\Pager\Wrapper;
use Gloomy\PagerBundle\Pager\Field;

use Doctrine\DBAL\Query\QueryBuilder;
// use Doctrine\ORM\Query\Expr;

class DbalQueryBuilderWrapper implements Wrapper
{
    static public $logicalAND   = array(array(array('o' => 'and')));
    static public $logicalOR    = array(array(array('o' => 'or')));

    protected $_builder = null;
    protected $_count   = null;
    protected $_fields  = null;
    protected $_config  = null;

    public function __construct(QueryBuilder $builder, $fields = array(), $config = array())
    {
        $this->_builder = $builder;
        $this->_fields  = $fields;
        $this->_config  = $config;

        if (empty($this->_fields)) {
            $this->populateFields();
        }
    }

    protected function populateFields()
    {
        // TODO
    }

    public function addField(Field $field, $alias = null)
    {
        if (is_null($alias)) {
            $alias = $field->getProperty();
        }
        $this->_fields[$alias] = $field;
        return $this;
    }

    public function getQueryBuilder()
    {
        return $this->_builder;
    }

    public function setConfig($config)
    {
        $this->_config = $config;
        return $this;
    }

    public function addConfig($key, $value)
    {
        $this->_config[$key] = $value;
        return $this;
    }

    public function count()
    {
        if (is_null($this->_count)) {
            $builder        = clone $this->_builder;
            $this->_count   = $builder
                ->select('count(1)')
                ->resetQueryPart('orderBy')
                ->execute()
                    ->fetchColumn();
        }

        return $this->_count;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        return $this->_builder
            ->setFirstResult($offset)
            ->setMaxResults($itemCountPerPage)
            ->execute()
        ;
    }

    public function setOrderBy(array $orderBy)
    {
        $first    = true;
        foreach ($orderBy as $alias => $order) {
            $sort    = $this->getField($alias)->getQualifier();
            if ($first) {
                $this->_builder->orderBy($sort, $order);
                $first = false;
            }
            else {
                $this->_builder->addOrderBy($sort, $order);
            }
        }
        return $this;
    }

    public function setFilters(array $fields, array $values, array $operators = array(), array $logical = array())
    {
        static $paramNum;

        if (empty($fields)) {
            return $this;
        }

        // If we modify the query, we must recount the items
        $this->_count    = null;

        $criteria       = array();
        $expr           = $this->_builder->expr();

        foreach ($fields as $key => $alias) {

            $field      = $this->getField($alias);
            $qualifier  = $field->getQualifier();

            $value      = array_key_exists($key, $values) ? $values[$key] : '';
            if ('date' === $field->getType() && $value) {
                $date   = \DateTime::createFromFormat($field->getDateFormat(), $value);
//                 if ($date) {
//                     $value = $date->format('Y-m-d');
//                 }
            }

            $operator   = array_key_exists($key, $operators) ? $operators[$key] : 'contains';
            $paramName  = ':param'.++$paramNum;

            if (is_string($value) && ! strlen($value) && ! in_array($operator, array("null", "notNull", "n", "nn"))) {
                $criteria[]    = null;
                continue;
            }

            switch ( $operator ) {

                default:
                case "c":
                case "contains":
                    if (isset($this->_config['force_case_insensitive'])) { // Force case insensitive (ie. for Oracle)
                        $criteria[]    = $expr->comparison('UPPER('.$qualifier.')', 'LIKE', 'UPPER('.$paramName.')');
                    }
                    else {
                        $criteria[]    = $expr->comparison($qualifier, 'LIKE', $paramName);
                    }
                    $this->_builder->setParameter($paramName, '%'.$value.'%');
                    break;

                case "nc":
                case "notContains":
                    if (isset($this->_config['force_case_insensitive'])) { // Force case insensitive (ie. for Oracle)
                        $criteria[]    = $expr->comparison('UPPER('.$qualifier.')', 'NOT LIKE', 'UPPER('.$paramName.')');
                    }
                    else {
                        $criteria[]    = $expr->comparison($qualifier, 'NOT LIKE', $paramName);
                    }
                    $this->_builder->setParameter($paramName, '%'.$value.'%');
                    break;

                case "e":
                case "equals":
                    $criteria[]    = $expr->eq($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "ne":
                case "notEquals":
                    $criteria[]    = $expr->neq($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "g":
                case "greater":
                    $criteria[]    = $expr->gt($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "ge":
                case "greaterOrEquals":
                    $criteria[]    = $expr->gte($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "l":
                case "less":
                    $criteria[]    = $expr->lt($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "le":
                case "lessOrEquals":
                    $criteria[]    = $expr->lte($qualifier, $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "n":
                case "null":
                    $criteria[]    = $expr->isNull($qualifier);
                    break;

                case "nn":
                case "notNull":
                    $criteria[]    = $expr->isNotNull($qualifier);
                    break;

                case "i":
                case "in":
                    if ( ! is_array( $value ) ) {
                        $value     = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $value, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    $criteria[]    = $expr->comparison($qualifier, 'IN', $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;

                case "ni":
                case "notIn":
                    if ( ! is_array( $value ) ) {
                        $value     = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $value, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    $criteria[]    = $expr->comparison($qualifier, 'NOT IN', $paramName);
                    $this->_builder->setParameter($paramName, $value);
                    break;
            }
        }

        if (empty($logical)) {
            $logical           = self::$logicalAND;
        }
        else {
            rsort( $logical ); // Start with last layer
        }

        foreach ($logical as $layer) {

            $criteriumIndex    = 0;
            $concatCriteria    = array();

            foreach ($layer as $condition) {

                if (array_key_exists('operator', $condition)) {
                    $operator  = $condition['operator'];
                }
                elseif (array_key_exists('o', $condition)) {
                    $operator  = $condition['o'];
                }
                else {
                    $operator  = 'and';
                }

                if (array_key_exists('count', $condition)) {
                    $count     = $condition['count'];
                }
                elseif (array_key_exists('c', $condition)) {
                    $count     = $condition['c'];
                }
                else {
                    $count     = count($criteria) - $criteriumIndex;
                }

                // Criteria for the operator
                $subCriteria   = array();
                foreach (array_slice($criteria, $criteriumIndex, $count) as $criterium) {
                    if ($criterium) {
                        $subCriteria[] = $criterium;
                    }
                }
                $criteriumIndex += $count;

                // Apply operator
                if (count($subCriteria) > 1) {
                    $method    = (strtolower($operator) == 'or') ? 'orX' : 'andX';
                    $concatCriteria[] = ($subCriteria) ? call_user_func_array(array($this->_builder->expr(), $method), $subCriteria)
                                                       : null;
                }
                else {
                    $concatCriteria[] = current($subCriteria);
                }

                // Complete array
                for ($count--; $count; $count--) {
                    $concatCriteria[] = null;
                }
            }
            $criteria    = $concatCriteria;
        }

        $criterium    = current($criteria);
        if ($criterium) {
            $this->_builder->andWhere($criterium);
        }

        return $this;
    }

    protected function getField($alias)
    {
        if (!isset($this->_fields[$alias])) {
            throw new \Exception('Unknown alias '.$alias);
        }
        return $this->_fields[$alias];
    }
}
