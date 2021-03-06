<?php
namespace p2ee\Preparables;

class PreparerTest extends \PHPUnit_Framework_TestCase {

    public function testCreation() {
        $preparer = new Preparer([]);
        $this->assertInstanceOf(Preparer::class, $preparer);
    }

    public function testObjectPreparation() {
        $testValue = 1;
        $testKey = 'test1';

        $requirement = new TestRequirement($testKey, false);

        $preparable = $this->buildPreparableMock($requirement, $testKey, $testValue);

        $resolver = $this->buildResolverMock($requirement, $testValue);

        $preparer = new Preparer([
            TestRequirement::class => $resolver
        ]);

        $preparer->prepare($preparable);
    }

    public function testResolverCaching() {
        $testValue = 1;
        $testKey = 'test1';

        $requirement1 = new TestRequirement($testKey, true);
        $requirement2 = new TestRequirement($testKey, true);

        $preparable1 = $preparable = $this->buildPreparableMock($requirement1, $testKey, $testValue);

        $preparable2 = $preparable = $this->buildPreparableMock($requirement2, $testKey, $testValue);

        $resolver = $this->buildResolverMock($requirement1, $testValue);

        $preparer = new Preparer([
            TestRequirement::class => $resolver
        ]);

        $preparer->prepare($preparable1);
        $preparer->prepare($preparable2);
    }

    public function testPrefills() {
        $testValue = 1;
        $testKey = 'test1';

        $requirement = new TestRequirement($testKey, true);

        $preparable = $this->buildPreparableMock($requirement, $testKey, $testValue);

        $resolver = $this->buildResolverMock($requirement, $testValue, 0);

        $preparer = new Preparer([
            TestRequirement::class => $resolver
        ]);

        $preparer->prepare($preparable, [$testKey => $testValue]);
    }

    public function testUnusedPrefillsShouldNotBePrefilled() {
        $testValue = 1;
        $testKey = 'test1';

        $requirement = new TestRequirement($testKey, true);

        $preparable = $this->buildPreparableMock($requirement, $testKey, $testValue);

        $resolver = $this->buildResolverMock($requirement, $testValue, 1);

        $preparer = new Preparer([
            TestRequirement::class => $resolver
        ]);

        $preparer->prepare($preparable, [$testKey . '123' => $testValue]);
    }

    /**
     * @param $requirement
     * @param $testValue
     * @param int $resolveCalls
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildResolverMock($requirement, $testValue, $resolveCalls = 1) {
        if ($resolveCalls == 0) {
            $expectation = $this->never();
        } else if ($resolveCalls == 1) {
            $expectation = $this->once();
        } else {
            $expectation = $this->exactly($resolveCalls);
        }

        $resolver = $this->getMock(Resolver::class);

        if ($resolveCalls == 0) {
            $resolver->expects($expectation)
                ->method('resolve');
        } else {
            $resolver->expects($expectation)
                ->method('resolve')->with(
                    $this->identicalTo($requirement)
                )->will($this->returnValue($testValue));
        }
        return $resolver;
    }

    /**
     * @param $requirement
     * @param $testKey
     * @param $testValue
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildPreparableMock($requirement, $testKey, $testValue) {
        $preparable = $this->getMock(Preparable::class);

        $preparable->expects($this->once())
            ->method('collect')
            ->will($this->returnCallback(function () use ($requirement) {
                yield [
                    $requirement
                ];
            }));

        $preparable->expects($this->once())
            ->method('inject')
            ->with(
                $this->equalTo($testKey),
                $this->equalTo($testValue)
            );
        return $preparable;
    }
}

class TestRequirement extends Requirement {
    /**
     * @var
     */
    private $cachable;

    public function __construct($key, $cachable) {
        $this->key = $key;
        $this->cachable = $cachable;
    }

    public function isCacheable() {
        return $this->cachable;
    }

    public function getCacheKey() {
        return $this->getKey();
    }

    public function getPrefills() {
        return [];
    }
}