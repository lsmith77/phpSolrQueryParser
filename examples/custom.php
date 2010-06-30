<?php

require '../phpSolrQueryToken.php';
require '../phpSolrQueryTerm.php';
require '../phpSolrQueryParser.php';
$factory = function() { return new phpSolrQueryTerm(); };

// #1
$q = '"security council" foo:lala +dong (doing)';
$parser = new phpSolrQueryParser($factory);

$tokens = $parser->parse($q);
$terms = $parser->processTerms($tokens);
var_dump($q);
var_dump($terms->serialize());
var_dump($terms);

// #2
$q = '"security council" -foo:(bar OR doing OR lala) (check AND (foo OR BAR))';
require '../phpSolrQueryParserBraces.php';
$parser = new phpSolrQueryParserBraces($factory);

$tokens = $parser->parse($q);
$terms = $parser->processTerms($tokens);
var_dump($q);
var_dump($terms->serialize());
var_dump($terms);

// #3
$q = '"security council" ding -code:A/RES/*';
require 'phpSolrQueryTermCustom.php';
require 'Exception.php';
require 'HttpTransportException.php';
require 'InvalidArgumentException.php';
require 'NoServiceAvailableException.php';
require 'Response.php';
require 'Document.php';
require 'Service.php';
require 'sfLuceneService.class.php';
require 'sfLuceneCriteria.class.php';
$criteria = new sfLuceneCriteria;
$stack = array(new phpSolrQueryTermCustom('AND', $criteria));
$parser = new phpSolrQueryParser($factory);

$tokens = $parser->parse($q, $stack);
$terms = $parser->processTerms($tokens);
var_dump($q);
var_dump($terms->serialize());
var_dump($terms);
