<?php

class phpSolrQueryTermCustom extends phpSolrQueryTerm {
    protected $criteria;

    public function __construct($op = '', $criteria = null)
    {
        $this->op = $op;
        $this->criteria = $criteria;
    }

    protected function processTerm($term) {
        $prefix = $term->prefix;
        $field = $term->field;
        $term = (string)$term;
        $op = $this->op;
        $op = rtrim(" $op ").' ';
        if (!empty($field)) {
            switch ($field) {
            case 'code':
                $field = substr($term, -1, 1) === '*' ? 'document_code_prefix' : 'document_code';
                $term = $field === 'document_code_prefix' ? substr($term, 0, -1) : $term;
                break;
            case 'tag':
                $field = 'tag_ids';
/*
                $q = Doctrine_Query::create()
                    ->select('t.id')
                    ->from('Tag t')
                    ->where('t.name = ?', array($term));
                $term = $q->execute(array(), Doctrine_Core::HYDRATE_SINGLE_SCALAR);
*/
                $term = rand(1, 100);
                break;
            default:
                throw new Exception('Unsupported field: '.$field);
            }
            $this->criteria->addField($prefix.$field, $term, $op, true);
            return;
        }
        $term = parent::processTerm($term);
        return $prefix.$term;
    }

    public function serialize()
    {
        $terms = $this->terms;
        $op = $this->op;
        $op = rtrim(" $op ").' ';
        $dismax = array();
        foreach ($terms as $key => $term) {
            if ($term instanceOf phpSolrQueryTerm) {
                $term = $this->processTerm($term);
                if (!is_null($term)) {
                    $dismax[] = $term;
                }
            } else {
                $dismax[] = parent::processTerm($term);
            }
        }
        if (!empty($dismax)) {
            $dismax = implode(' ', $dismax);
            $subcritieria = new sfLuceneCriteria;
            $subcritieria->add('_query_:"{!dismax qf=\'content document_title\' pf=\'content document_title\' mm=0 v=$qq}"', $op, true);
            $this->criteria->add($subcritieria, 'AND');
            $this->criteria->addParam('qq', $dismax);
        }

        $q = $this->criteria->getParams();
        $q['q'] = array($this->criteria->getQuery());
        return $q;
    }
}
