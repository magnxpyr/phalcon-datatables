<?php
namespace DataTables\Adapters;
use Phalcon\Paginator\Adapter\QueryBuilder as PQueryBuilder;

class QueryBuilder extends AdapterInterface{
  protected $builder;
  private $global_search;
  private $column_search;
  private $_bind;

  public function setBuilder($builder) {
    $this->builder = $builder;
  }

  public function getResponse() {
    $builder = new PQueryBuilder([
      'builder' => $this->builder,
      'limit'   => 1,
      'page'    => 1,
    ]);

    $total = $builder->getPaginate();
    $this->global_search = [];
    $this->column_search = [];

    $this->bind('global_search', function($column, $search) {
      $this->global_search[] = "{$column} LIKE :keyg_{$column}:";
      $this->_bind["keyg_" . $column] = "%{$search}%";
    });

    $this->bind('column_search', function($column, $search) {
      $this->column_search[] = "{$column} LIKE :keyc_{$column}:";
      $this->_bind["keyc_" . $column] = "%{$search}%";
    });

    $this->bind('order', function($order) {
      if (!empty($order)) {
        $this->builder->orderBy(implode(', ', $order));
      }
    });

    if (!empty($this->global_search) || !empty($this->column_search)) {
      $where = implode(' OR ', $this->global_search);
      if (!empty($this->column_search))
        $where = (empty($where) ? '' : ('(' . $where . ') AND ')) . implode(' AND ', $this->column_search);
      $this->builder->andWhere($where, $this->_bind);
    }

    $builder = new PQueryBuilder([
      'builder' => $this->builder,
      'limit'   => $this->parser->getLimit($total->total_items),
      'page'    => $this->parser->getPage(),
    ]);

    $filtered = $builder->getPaginate();

    return $this->formResponse([
      'total'     => $total->total_items,
      'filtered'  => $filtered->total_items,
      'data'      => $filtered->items->toArray(),
    ]);
  }
}
