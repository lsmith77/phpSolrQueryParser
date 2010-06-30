<?php
/**
 * File containing solr query parser class
 * Adapted from ezcSearchQueryBuilder and ezcSearchQueryToken
 * http://svn.ezcomponents.org/viewvc.cgi/trunk/Search/src/
 *
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

class phpSolrQueryParser
{
    protected $state;

    protected $preEscapeState;

    protected $stack;

    protected $stackLevel;

    protected $stackType;

    protected $string;

    protected $prefix;

    protected $field;

    protected $factory;

    public function __construct($factory)
    {
        $this->setFactory($factory);
    }

    public function setFactory($factory)
    {
        $this->factory = $factory;
    }

    public function parse($searchQuery, $stack = null)
    {
        $this->reset($stack);

        return $this->tokenize($searchQuery);
    }

    public function reset($stack = null)
    {
        $this->state = 'normal';
        $this->preEscapeState = 'normal';
        $this->stackLevel = 0;
        if (is_array($stack)) {
            $this->stack = $stack;
        } else {
            $this->stack = array();
            $factory = $this->factory;
            $this->stack[$this->stackLevel] = $factory();
        }
        $this->stackType = array();
        $this->stackType[$this->stackLevel] = 'default';
        $this->string = '';
        $this->prefix = '';
        $this->field = '';
    }

    protected function getMap()
    {
        return array(
            ' '  => phpSolrQueryToken::SPACE,
            '\t' => phpSolrQueryToken::SPACE,
            '"'  => phpSolrQueryToken::QUOTE,
            '+'  => phpSolrQueryToken::PLUS,
            '-'  => phpSolrQueryToken::MINUS,
            'and' => phpSolrQueryToken::LOGICAL_AND,
            'or'  => phpSolrQueryToken::LOGICAL_OR,
            ':'  => phpSolrQueryToken::COLON,
            '(' => phpSolrQueryToken::BRACE_OPEN,
            ')' => phpSolrQueryToken::BRACE_CLOSE,
        );
    }

    protected function getTokenizerRegexp()
    {
        return '@(\s)|(["+():-])@';
    }

    protected function tokenize($searchQuery)
    {
        $map = $this->getMap();
        $tokens = array();
        $tokenArray = preg_split($this->getTokenizerRegexp(), $searchQuery, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($tokenArray as $token) {
            if (isset($map[strtolower($token)])) {
                $tokens[] = new phpSolrQueryToken($map[strtolower($token)], $token);
            } else {
                $tokens[] = new phpSolrQueryToken(phpSolrQueryToken::STRING, $token);
            }
        }
        return $tokens;
    }

    public function processToken($token)
    {
        $factory = $this->factory;
        switch ($this->state) {
        case 'normal':
            switch ($token->type) {
            case phpSolrQueryToken::STRING:
                $this->string .= $token->token;
                if (substr($this->string, -1, 1) === '\\') {
                    $this->preEscapeState = $this->state;
                    $this->state = 'in-escape';
                    break;
                }
                if ($this->prefix || $this->field) {
                    $term = $factory();
                    $term->addTerm($this->string);
                    $term->setPrefix($this->prefix);
                    $term->setField($this->field);
                } else {
                    $term = $this->string;
                }
                $this->stack[$this->stackLevel]->addTerm($term);
                $this->string = $this->prefix = $this->field = '';
                break;
            case phpSolrQueryToken::COLON:
                if ($this->field !== '') {
                    throw new Exception('Unescaped colon may not be part of a fielded string');
                }
                $this->field = $this->stack[$this->stackLevel]->popTerm();
                if ($this->field instanceOf phpSolrQueryTerm) {
                    $terms = $this->field->getTerms();
                    if (empty($terms) || count($terms) > 1) {
                        throw new Exception('Field name must be a string');
                    }
                    $this->prefix = $this->field->getPrefix();
                    $this->field = reset($terms);
                } else {
                    $this->prefix = $this->stack[$this->stackLevel]->getPrefix();
                }
                break;
            case phpSolrQueryToken::SPACE:
                if ($this->string !== '') {
                    if ($this->prefix || $this->field) {
                        $term = $factory();
                        $term->addTerm($this->string);
                        $term->setPrefix($this->prefix);
                        $term->setField($this->field);
                    } else {
                        $term = $this->string;
                    }
                    $this->stack[$this->stackLevel]->addTerm($term);
                    $this->string = $this->prefix = $this->field = '';
                }
                break;
            case phpSolrQueryToken::ESCAPE:
                $this->string = $token->token;
                $this->preEscapeState = $this->state;
                $this->state = 'in-escape';
                break;
            case phpSolrQueryToken::QUOTE:
                $this->state = 'in-quotes';
                $this->string = '';
                break;
            case phpSolrQueryToken::LOGICAL_OR:
                if ($this->stackType[$this->stackLevel] === 'and') {
                    throw new Exception('You can not mix AND and OR without using "(" and ")".');
                }
                $this->stackType[$this->stackLevel] = 'or';
                break;
            case phpSolrQueryToken::LOGICAL_AND:
                if ($this->stackType[$this->stackLevel] === 'or') {
                    throw new Exception('You can not mix OR and AND without using "(" and ")".');
                }
                $this->stackType[$this->stackLevel] = 'and';
                break;
            case phpSolrQueryToken::PLUS:
            case phpSolrQueryToken::MINUS:
                if ($this->prefix !== '') {
                    throw new Exception('No prefix allowed after a prefix');
                }
                $this->prefix = $token->token;
                break;
            case phpSolrQueryToken::BRACE_OPEN:
            case phpSolrQueryToken::BRACE_CLOSE:
                $this->string .= '\\'.$token->token;
                break;
            }
            break;
        case 'in-escape':
            switch ($token->type) {
            case phpSolrQueryToken::STRING:
            case phpSolrQueryToken::COLON:
            case phpSolrQueryToken::SPACE:
            case phpSolrQueryToken::QUOTE:
            case phpSolrQueryToken::LOGICAL_AND:
            case phpSolrQueryToken::LOGICAL_OR:
            case phpSolrQueryToken::PLUS:
            case phpSolrQueryToken::MINUS:
            case phpSolrQueryToken::BRACE_OPEN:
            case phpSolrQueryToken::BRACE_CLOSE:
                $this->string .= $token->token;
            case phpSolrQueryToken::ESCAPE:
                $this->state = $this->preEscapeState;
                break;
            }
            break;
        case 'in-quotes':
            switch ($token->type) {
            case phpSolrQueryToken::ESCAPE:
                $this->string .= $token->token;
                $this->preEscapeState = $this->state;
                $this->state = 'in-escape';
                break;
            case phpSolrQueryToken::QUOTE:
                $this->string = trim($this->string);
                if (empty($this->string)) {
                    throw new Exception('Filter may not be empty');
                }
                if ($this->prefix || $this->field) {
                    $term = $factory();
                    $term->addTerm($this->string);
                    $term->setPrefix($this->prefix);
                    $term->setField($this->field);
                } else {
                    $term = $this->string;
                }
                $this->stack[$this->stackLevel]->addTerm($term);
                $this->string = $this->prefix = $this->field = '';
                $this->state = 'normal';
                break;

            case phpSolrQueryToken::STRING:
            case phpSolrQueryToken::COLON:
            case phpSolrQueryToken::SPACE:
            case phpSolrQueryToken::LOGICAL_AND:
            case phpSolrQueryToken::LOGICAL_OR:
            case phpSolrQueryToken::PLUS:
            case phpSolrQueryToken::MINUS:
            case phpSolrQueryToken::BRACE_OPEN:
            case phpSolrQueryToken::BRACE_CLOSE:
                $this->string .= $token->token;
                break;
            }
            break;
        }

        return true;
    }

    public function processTerms($tokens)
    {
        $factory = $this->factory;
        foreach ($tokens as $token) {
            $this->processToken($token);
        }

        if ($this->string !== '') {
            if ($this->prefix || $this->field) {
                $term = $factory();
                $term->addTerm($this->string);
                $term->setPrefix($this->prefix);
                $term->setField($this->field);
            } else {
                $term = $this->string;
            }
            $this->stack[$this->stackLevel]->addTerm($term);
        }

        if ($this->state == 'in-quotes') {
            throw new Exception('Unterminated quotes in query string.');
        }

        if (empty($this->stack[0])) {
            return array();
        }

        return $this->stack[0];
    }
}
