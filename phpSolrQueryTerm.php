<?php

class phpSolrQueryTerm
{
    protected $terms = array();

    protected $op;

    protected $prefix = '';

    protected $field = '';

    public function __construct($op = '')
    {
        $this->op = $op;
    }

    public function addTerm($term)
    {
        $this->terms[] = $term;
    }

    public function getTerms()
    {
        return $this->terms;
    }

    public function setTerms($terms)
    {
        $this->terms = array();
        foreach ($terms as $term) {
            $this->addTerm($term);
        }
    }

    public function setOp($op)
    {
        $this->op = $op;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function popTerm()
    {
        return array_pop($this->terms);
    }

    protected function processTerm($term) {
        if ($term instanceOf phpSolrQueryTerm) {
            $field = $term->field === '' ? $term->prefix : $term->prefix.$term->field.':';
            $term = $field.(string)$term;
        } elseif (strpos($term, ' ') !== false) {
            $term = '"'.$term.'"';
        }
        return $term;
    }

    public function serialize()
    {
        $terms = $this->terms;
        $op = $this->op;
        foreach ($terms as $key => $term) {
            $terms[$key] = $this->processTerm($term);
        }
        if (count($terms) == 1) {
            return reset($terms);
        }
        $op = rtrim(" $op ").' ';
        return '('.implode($op, $terms).')';
    }

    public function __toString() {
        return (string)$this->serialize();
    }
}
