<?php
namespace tests\tarsys;

use tarsys\AqlGen\AqlFunction;
use tarsys\AqlGen\AqlGen;
use tarsys\AqlGen\AqlFilter;

/**
 * Class to build AQL strings
 *
 * @author Társis Lima
 */
class AqlGenTest extends \PHPUnit_Framework_TestCase
{
    public function testQuery()
    {

        $aql = AqlGen::query('u', 'users');
        $this->assertTrue($aql instanceof AqlGen);
        $string = $aql->get();

        $this->assertEquals("FOR u IN users\nRETURN u", $string);
    }

    public function testQueryWithOtherQueryInCollection()
    {
        $collectionQuery = $aql = AqlGen::query('g', 'groups');
        $collectionQuery->bindParams(array('id', '10'));

        $aql = AqlGen::query('u', $collectionQuery);
        $this->assertTrue($aql instanceof AqlGen);
        $string = $aql->get();

        $this->assertEquals("FOR u IN (\n\tFOR g IN groups\nRETURN g)\nRETURN u", $string);
    }

    public function testQueryWithArrayInCollection()
    {
        //need implementation
    }

    public function testReturn()
    {
        $aql = AqlGen::query('u', 'users')->setReturn("{'name': u.name}");
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\nRETURN {'name': u.name}", $string);

        $returnData = array(
            'name' => 'u.name'
        );
        $aql = AqlGen::query('u', 'users')->setReturn($returnData);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\nRETURN {\"name\":\"u.name\"}", $string);
    }

    public function testSort()
    {
        $aql = AqlGen::query('u', 'users')->sort('u.name');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tSORT u.name ASC\nRETURN u", $string);

        $aql = AqlGen::query('u', 'users')->sort('u.name', AqlGen::SORT_DESC);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tSORT u.name DESC\nRETURN u", $string);

        $aql = AqlGen::query('u', 'users')->sort(array('u.name', 'u.points'), AqlGen::SORT_ASC)
            ->sort(array('u.age'), AqlGen::SORT_DESC);

        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tSORT u.name, u.points ASC, u.age DESC\nRETURN u", $string);
    }

    public function testLimit()
    {
        $aql = AqlGen::query('u', 'users')->limit(10);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tLIMIT 10\nRETURN u", $string);
    }

    public function testLimitWithOfset()
    {
        $aql = AqlGen::query('u', 'users')->skip(2);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\nRETURN u", $string);

        $aql = AqlGen::query('u', 'users')->limit(10)->skip(2);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tLIMIT 2, 10\nRETURN u", $string);
    }

    public function testSubqueryNotHaveReturn()
    {
        $friendsQuery = AqlGen::query('f', 'friends');

        $aql = AqlGen::query('u', 'users')
            ->subquery($friendsQuery);

        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tFOR f IN friends\nRETURN u", $string);
    }

    public function testSubqueryWithReturnThrowError()
    {
        $friendsQuery = AqlGen::query('f', 'friends');
        $friendsQuery->setReturn('f');
        $aql = AqlGen::query('u', 'users')
            ->subquery($friendsQuery);

        $this->setExpectedException('InvalidArgumentException', "A subquery not should have a RETURN operation.");
        $aql->get();
    }

    public function testLetWithVar()
    {
        $aql = AqlGen::query('u', 'users')->let('points', '10');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tLET points = 10\nRETURN u", $string);
    }

    public function testLetWithSubquery()
    {
        $friendsQuery = AqlGen::query('f', 'friends');
        $aql = AqlGen::query('u', 'users')->let('points', $friendsQuery);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tLET points = (FOR f IN friends\nRETURN f)\nRETURN u", $string);
    }

    public function testLetWithFunctionOverSubquery()
    {
        $friendsQuery = AqlGen::query('f', 'friends');
        $aql = AqlGen::query('u', 'users')->let('points', new AqlFunction('FIRST', [$friendsQuery]));
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tLET points = FIRST ((FOR f IN friends\nRETURN f))\nRETURN u", $string);
    }

    public function testCollect()
    {
        $aql = AqlGen::query('u', 'users')->collect('points', 'u.name');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tCOLLECT points = u.name\nRETURN u", $string);

        $aql = AqlGen::query('u', 'users')->collect('points', 'u.name', 'group');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tCOLLECT points = u.name INTO group\nRETURN u", $string);
    }

    public function testAndFilter()
    {
        $aql = AqlGen::query('u', 'users')->filter('u.active = true');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tFILTER u.active = true\nRETURN u", $string);

        $aql = AqlGen::query('u', 'users')->filter('u.active = true')->filter('u.age > 20');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tFILTER u.active = true && u.age > 20\nRETURN u", $string);
    }

    public function testAndFilterWithParams()
    {
        $aql = AqlGen::query('u', 'users')->filter('u.age = @age', ['age' => 20]);
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tFILTER u.age = @age\nRETURN u", $string);
        $params = $aql->getParams();
        $this->assertArrayHasKey('age', $params);
        $this->assertEquals($params['age'], 20);
    }

    public function testOrFilter()
    {
        $aql = AqlGen::query('u', 'users')->filter('u.active = true')->orFilter('u.age > 20');
        $string = $aql->get();
        $this->assertEquals("FOR u IN users\n\tFILTER u.active = true || u.age > 20\nRETURN u", $string);
    }

    public function testObjectFilter()
    {
        $filter = new AqlFilter();
        $filter->andFilter('u.active = true');
        $filter->andFilter('u.age > @minAge');
        $filter->andFilter('u.age < @maxAge');
        $filter->bindParams(
            [
                'minAge' => 20,
                'maxAge' => 50,
            ]
        );

        $aql = AqlGen::query('u', 'users')->filter($filter);
        $string = $aql->get();
        $this->assertEquals(
            "FOR u IN users\n\tFILTER u.active = true && u.age > @minAge && u.age < @maxAge\nRETURN u",
            $string
        );

        $params = $aql->getParams();
        $this->assertArrayHasKey('minAge', $params);
        $this->assertEquals($params['minAge'], 20);

        $this->assertArrayHasKey('maxAge', $params);
        $this->assertEquals($params['maxAge'], 50);
    }


    public function testReturnStatementNotExistByDefaultInChangeOperations()
    {
        $aql = AqlGen::query('u', 'users');
        $data = array(
            'name' => 'Jhon'
        );

        $aql->insert($data, 'backup');
        $this->assertEquals("FOR u IN users\nINSERT {\"name\":\"Jhon\"} IN backup ", $aql->get());

    }


    public function testReturnExistInChangeOperationsWhenCalled()
    {
        $aql = AqlGen::query('u', 'users');
        $data = array(
            'name' => 'Jhon'
        );

        $aql->insert($data, 'backup');
        $aql->setReturn('u');
        $this->assertEquals("FOR u IN users\nINSERT {\"name\":\"Jhon\"} IN backup RETURN u", $aql->get());
    }

    public function testInsertOperation()
    {
        $aql = AqlGen::query('u', 'users')
            ->insert('u', 'backup');

        $this->assertEquals("FOR u IN users\nINSERT u IN backup ", $aql->get());

        //insert document
        $data = array(
            'name' => AqlGen::expr('u.name'),
            'type' => "A"
        );

        $aql = AqlGen::query('u', 'users')
            ->insert($data, 'backup');

        $this->assertEquals("FOR u IN users\nINSERT {\"name\":u.name,\"type\":\"A\"} IN backup ", $aql->get());
    }

    public function testUpdateOperation()
    {
        $data = array(
            'status' => "inactive"
        );

        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == 0')
            ->update($data);

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == 0\nUPDATE u WITH {\"status\":\"inactive\"} IN users ", $aql->get());

        //update other collection
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == 0')
            ->update($data, 'backup');

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == 0\nUPDATE u WITH {\"status\":\"inactive\"} IN backup ", $aql->get());

        //with options
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == 0')
            ->update($data, 'users', array('waitForSync' => true));

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == 0\nUPDATE u WITH {\"status\":\"inactive\"} IN users  OPTIONS {\"waitForSync\":true} ", $aql->get());

        //with return
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == 0')
            ->update($data)
            ->setReturn('NEW');

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == 0\nUPDATE u WITH {\"status\":\"inactive\"} IN users RETURN NEW", $aql->get());

        //without data
        $data = array();
        $aql = AqlGen::query('u', 'users')
            ->update($data, 'users');

        $this->assertEquals("FOR u IN users\nUPDATE u WITH {} IN users ", $aql->get());
    }

    public function testReplaceOperation()
    {
        $data = array(
            'status' => 'active'
        );

        $aql = AqlGen::query('u', 'users')
            ->replace($data);

        $this->assertEquals("FOR u IN users\nREPLACE u WITH {\"status\":\"active\"} IN users ", $aql->get());

        //replace in other collection

        $aql = AqlGen::query('u', 'users')
            ->replace($data, 'backup');

        $this->assertEquals("FOR u IN users\nREPLACE u WITH {\"status\":\"active\"} IN backup ", $aql->get());

        //options
        $aql = AqlGen::query('u', 'users')
            ->replace($data, null, array('waitForSync' => true));

        $this->assertEquals("FOR u IN users\nREPLACE u WITH {\"status\":\"active\"} IN users  OPTIONS {\"waitForSync\":true} ", $aql->get());
    }

    public function testRemoveOperation()
    {
        //same collection
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == deleted')
            ->remove();

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == deleted\nREMOVE u IN users ", $aql->get());

        //other collection
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == deleted')
            ->remove('u', 'backup');

        $this->assertEquals("FOR u IN users\n\tFILTER u.status == deleted\nREMOVE u IN backup ", $aql->get());
        $aql = AqlGen::query('u', 'users')
            ->filter('u.status == deleted')
            ->remove('u', 'backup', array('waitForSync' => true));
        $this->assertEquals("FOR u IN users\n\tFILTER u.status == deleted\nREMOVE u IN backup  OPTIONS {\"waitForSync\":true} ", $aql->get());
    }


    public function testAqlExpressionRemoveQuotes()
    {
        $data = array(
            'value' => AqlGen::expr('FLOOR(i + 100)')
        );

        $aql = AqlGen::query('i', '1..10')
            ->insert($data, 'backup');

        $this->assertEquals("FOR i IN 1..10\nINSERT {\"value\":FLOOR(i + 100)} IN backup ", $aql->get());
    }

    public function testLetWithFilter()
    {

    }
}
