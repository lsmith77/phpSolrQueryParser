<?php
/**
 * File containing solr query parser class
 * Adapted from ezcSearchQueryBuilder and ezcSearchQueryToken
 * http://svn.ezcomponents.org/viewvc.cgi/trunk/Search/src/
 *
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class phpSolrQueryParserBraces extends phpSolrQueryParser
{
    public function processToken($token)
    {
        if ($token->type !== phpSolrQueryToken::BRACE_OPEN
            && $token->type !== phpSolrQueryToken::BRACE_CLOSE
        ) {
            return parent::processToken($token);
        }

        $factory = $this->factory;
        switch ($this->state) {
        case 'normal':
            switch ($token->type) {
            case phpSolrQueryToken::BRACE_OPEN:
                // TODO: having to increase the stack level twice is a hack
                $this->stackLevel++;
                $this->stack[$this->stackLevel] = $factory();
                $this->stack[$this->stackLevel]->setPrefix($this->prefix);
                $this->stack[$this->stackLevel]->setField($this->field);
                $this->prefix = $this->field = '';
                $this->stackLevel++;
                $this->stack[$this->stackLevel] = $factory();
                $this->stackType[$this->stackLevel] = 'default';
                break;
            case phpSolrQueryToken::BRACE_CLOSE:
                $op = ($this->stackType[$this->stackLevel] == 'and' || $this->stackType[$this->stackLevel] == 'default')
                    ? 'AND' : 'OR';
                $term = $this->stack[$this->stackLevel];
                unset($this->stack[$this->stackLevel]);
                $this->stackLevel--;
                $term->setOp($op);
                $term->setPrefix($this->stack[$this->stackLevel]->getPrefix());
                $term->setField($this->stack[$this->stackLevel]->getField());
                unset($this->stack[$this->stackLevel]);
                $this->stackLevel--;
                $this->stack[$this->stackLevel]->addTerm($term);
                break;
            }
            break;
        case 'in-quotes':
        case 'in-escape':
            switch ($token->type) {
            case phpSolrQueryToken::BRACE_OPEN:
            case phpSolrQueryToken::BRACE_CLOSE:
                $this->string .= $token->token;
                break;
            }
            break;
        }

        return true;
    }
}
