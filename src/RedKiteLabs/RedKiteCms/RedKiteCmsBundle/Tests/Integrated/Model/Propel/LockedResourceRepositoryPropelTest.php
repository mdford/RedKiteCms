<?php
/**
 * This file is part of the RedKite CMS Application and it is distributed
 * under the MIT License. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license infuserRepositoryation, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT License
 *
 */

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Integrated\Model\Propel;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\ResourcesLocker\ResourcesLocker;

/**
 * LockedResourceRepositoryPropelTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class LockedResourceRepositoryPropelTest extends Base\BaseModelPropel
{
    private $factoryRepository;
    private $resourcesLocker;
    private $lockedResourceRepository;
    private $userId = 1;
    private $resource = '00aa00aa00aa00aa00aa00aa00aa00aa';

    protected function setUp()
    {
        parent::setUp();

        $container = $this->client->getContainer();
        $this->resourcesLocker = $container->get('red_kite_cms.resources_locker');
        $this->factoryRepository = $container->get('red_kite_cms.factory_repository');
        $this->lockedResourceRepository = $this->factoryRepository->createRepository('LockedResource');
    }

    /**
     * @expectedException RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\Content\General\InvalidArgumentTypeException
     * @expectedExceptionMessage exception_only_propel_locked_resource_objects_are_accepted
     */
    public function testRepositoryAcceptsOnlyLockedResourceObjects()
    {
        $this->lockedResourceRepository->setRepositoryObject(new \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Page());
    }
    
    public function testFetchResourceFromItsName()
    {
        $this->resourcesLocker->lockResource($this->userId, $this->resource);
        $resource = $this->lockedResourceRepository->fromResourceName($this->resource);
        $this->assertInstanceOf('\RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\LockedResource', $resource);
        $this->assertEquals($this->resource, $resource->getResourceName());
    }
        
    public function testFetchResourceFromItsNameByUser()
    {
        $this->resourcesLocker->lockResource($this->userId, $this->resource);
        $resource = $this->lockedResourceRepository->fromResourceNameByUser($this->userId, $this->resource);
        $this->assertInstanceOf('\RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\LockedResource', $resource);
        $this->assertEquals($this->resource, $resource->getResourceName());
        $this->assertEquals($this->userId, $resource->getUserId());
    }
        
    public function testFreeLockedResource()
    {
        $this->resourcesLocker->lockResource($this->userId, $this->resource);
        $resource = $this->lockedResourceRepository->fromResourceNameByUser($this->userId, $this->resource);
        $this->assertNotNull($resource);
        $this->assertEquals(1, $this->lockedResourceRepository->freeLockedResource($this->resource));
        $resource = $this->lockedResourceRepository->fromResourceNameByUser($this->userId, $this->resource);
        $this->assertNull($resource);
    }
        
    public function testFreeLockedResourceByUser()
    {
        $this->resourcesLocker->lockResource($this->userId, $this->resource);
        $resource = $this->lockedResourceRepository->fromResourceNameByUser($this->userId, $this->resource);
        $this->assertNotNull($resource);
        $this->assertEquals(1, $this->lockedResourceRepository->freeUserResource($this->userId));
        $resource = $this->lockedResourceRepository->fromResourceNameByUser($this->userId, $this->resource);
        $this->assertNull($resource);
    }
    
    public function testFreeExpiredResources()
    {
        $resourcesLocker = new ResourcesLocker($this->factoryRepository, 0);
        $resourcesLocker->lockResource($this->userId, $this->resource);
        $resourcesLocker->lockResource(2, '00bb00aa00aa00aa00aa00aa00aa00aa');
        $this->assertCount(2, $this->lockedResourceRepository->fetchResources());
        $this->assertEquals(2, $this->lockedResourceRepository->removeExpiredResources(time()));
        $this->assertCount(0, $this->lockedResourceRepository->fetchResources());
    }
    
    public function testFetchResources()
    {
        $this->resourcesLocker->lockResource($this->userId, $this->resource);
        $this->resourcesLocker->lockResource(2, '00bb00aa00aa00aa00aa00aa00aa00aa');        
        $this->resourcesLocker->lockResource(3, '00cc00aa00aa00aa00aa00aa00aa00aa');
        $this->assertCount(3, $this->lockedResourceRepository->fetchResources());
    }
}