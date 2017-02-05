<?php

require_once dirname(__FILE__) . '/../AbstractTestSuite.php';

use Maghead\Manager\ConnectionManager;
use Maghead\SqlBuilder\SqlBuilder;
use Maghead\Runtime\BaseModel;
use Maghead\Runtime\BaseCollection;
use Maghead\ConfigLoader;
use Maghead\Config;
use Maghead\Bootstrap;
use Maghead\Schema\SchemaGenerator;
use Maghead\Schema\DeclareSchema;
use Maghead\Result;
use Maghead\PDOExceptionPrinter;
use SQLBuilder\Driver\BaseDriver;
use CLIFramework\Logger;

use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;

class MagheadTestSuite extends AbstractTestSuite
{
    protected $config;

    protected $connManager;

    protected $bookRepo;

    protected $authorRepo;

    function initialize()
    {
        $loader = require_once "vendor/autoload.php";

        $this->config = ConfigLoader::loadFromArray([
            'data_source' => [
                'default' => 'master',
                'nodes' => [
                    'master' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => [ 'quote_table' => true ],
                    ],
                ],
            ],
        ]);
        $loader->addPsr4('AuthorBooks\\Model\\', __DIR__ . '/Model/');

        $connectionManager = ConnectionManager::getInstance();
        Bootstrap::setup($this->config, true);

        $this->con = $connectionManager->getConnection('master');
        $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initTables();

        $this->bookRepo = Book::defaultRepo();
        $this->authorRepo = Author::defaultRepo();
    }

	function clearCache()
	{

	}
	
	function beginTransaction()
	{
        $this->con->beginTransaction();
	}
	
	function commit()
	{
        $this->con->commit();
	}
	
	function runAuthorInsertion($i)
	{
        $ret = $this->authorRepo->create([
            'first_name' => 'John' . $i,
            'last_name' => 'Doe' . $i,
        ]);;
        $this->authors[]= $ret;
	}

	function runBookInsertion($i)
	{
        $ret = $this->bookRepo->create([
            'title' => 'Hello' . $i,
            'author_id' => $this->authors[array_rand($this->authors)]->key,
            'isbn' => '1234',
            'price' => $i,
        ]);
		$this->books[]= $ret;
	}
	
	function runPKSearch($i)
	{
        $author = $this->authorRepo->loadByPrimaryKey($this->authors[array_rand($this->authors)]->key);
	}
	
	function runComplexQuery($i)
	{
        $authors = new AuthorCollection;
        $authors->where()
            ->greaterThan('id', $this->authors[array_rand($this->authors)]->id)
            ->or()
            ->equal('(first_name || last_name)', 'John Doe');
        $authors->count();
	}

	function runHydrate($i)
	{
        $books = new BookCollection;
        $books->where()->greaterThan('price', $i);
        $books->limit(5);
        foreach ($books as $book) {
        }
	}

	function runJoinSearch($i)
	{
        $books = new BookCollection;
        $books->where()->equal('title', 'Hello' . $i);
        $books->joinTable('author', 'a');
        $books->first();
	}
}
