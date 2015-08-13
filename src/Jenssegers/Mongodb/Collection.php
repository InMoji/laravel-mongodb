<?php namespace Jenssegers\Mongodb;

use Exception;
use MongoCollection;

class Collection {

    /**
     * The connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The MongoCollection instance..
     *
     * @var MongoCollection
     */
    protected $collection;

    /**
     * Constructor.
     */
    public function __construct(Connection $connection, MongoCollection $collection)
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        for ($i = 0; $i < 5; $i++)
        {
            $start = microtime(true);

            try
            {
                $e = null;
                $result = call_user_func_array(array($this->collection, $method), $parameters);
                break;
            }
            catch (MongoCursorException $e)
            {
                if (strpos($e->getMessage(), 'Remote server has closed the connection') === false || $i >= 4)
                {
                    throw $e;
                }
            }
        }

        if ($this->connection->logging())
        {
            // Once we have run the query we will calculate the time that it took to run and
            // then log the query, bindings, and execution time so we will report them on
            // the event that the developer needs them. We'll log time in milliseconds.
            $time = $this->connection->getElapsedTime($start);

            $query = [];

            // Convert the query paramters to a json string.
            foreach ($parameters as $parameter)
            {
                try
                {
                    $query[] = json_encode($parameter);
                }
                catch (Exception $e)
                {
                    $query[] = '{...}';
                }
            }

            // Convert the query to a readable string.
            $queryString = "\t" . $this->collection->db . '.' . $this->collection->getName() . '.' . $method . '(' . inmplode(',', $query) . ')';

            $this->connection->logQuery($queryString, [], $time);
        }

        return $result;
    }

    /**
     * Return the native MOngoCollection object.
     *
     * @return MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->collection;
    }

}
