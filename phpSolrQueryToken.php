<?php

class phpSolrQueryToken
{
    const STRING = 1;
    const SPACE  = 2;
    const QUOTE  = 3;
    const PLUS   = 4;
    const MINUS  = 5;
    const BRACE_OPEN  = 6;
    const BRACE_CLOSE = 7;
    const LOGICAL_AND = 8;
    const LOGICAL_OR  = 9;
    const COLON  = 10;
    const ESCAPE  = 11;

    public $type;

    public $token;

    public function __construct($type, $token)
    {
        $this->type = $type;
        $this->token = $token;
    }
}
