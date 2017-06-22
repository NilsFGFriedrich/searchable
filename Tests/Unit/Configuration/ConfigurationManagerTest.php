<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration;

use PAGEmachine\Searchable\Service\ExtconfService;
use PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures\TcaDataCollectorFixture;
use PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures\TestDataCollectorFixture;
use PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures\TestIndexerFixture;
use PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures\TestMapperFixture;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \PAGEmachine\Searchable\Configuration\ConfigurationManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for ConfigurationManager
 */
class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var ExtonfService
     */
    protected $extconfService;

    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $this->configurationManager = new ConfigurationManager();

        $this->extconfService = $this->prophesize(ExtconfService::class);

        GeneralUtility::setSingletonInstance(ExtconfService::class, $this->extconfService->reveal());
    }

    /**
     * @test
     */
    public function mergesToplevelConfiguration()
    {
        $configuration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages'
                ],
            ],
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $expectedConfiguration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'customOption' => 1
                ],
            ],
        ];

        $this->assertEquals($expectedConfiguration, $this->configurationManager->getIndexerConfiguration());
    }

    /**
     * @test
     */
    public function doesNothingIfNoClassIsAvailable() {

        $configuration = [
            'pages' => [
                'config' => [
                    'type' => 'pages'
                ],
            ],
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $this->assertEquals($configuration, $this->configurationManager->getIndexerConfiguration());

    }

    /**
     * @test
     */
    public function mergesRecursiveConfiguration()
    {
        $configuration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'extconfOption' => 'foobar'
                        ]
                    ]
                ],
            ],
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $expectedConfiguration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'customOption' => 1,
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'extconfOption' => 'foobar',
                            'option1' => 1,
                            'option2' => 2
                        ]
                    ]
                ],
            ],
        ];

        $this->assertEquals($expectedConfiguration, $this->configurationManager->getIndexerConfiguration());        
        
    }

    /**
     * @test
     */
    public function mergesMultipleConfigurationsOnTheSameLevel() {
        $configuration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'extconfOption' => 'foobar',
                            'subCollectors' => [
                                'myType' => [
                                    'className' => TestDataCollectorFixture::class,
                                    'config' => [
                                        'subExtconfOption' => 'barbaz'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $expectedConfiguration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'customOption' => 1,
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'extconfOption' => 'foobar',
                            'option1' => 1,
                            'option2' => 2,
                            'subCollectors' => [
                                'myType' => [
                                    'className' => TestDataCollectorFixture::class,
                                    'config' => [
                                        'subExtconfOption' => 'barbaz',
                                        'option1' => 1,
                                        'option2' => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $this->assertEquals($expectedConfiguration, $this->configurationManager->getIndexerConfiguration());  
    }

    /**
     * @test
     */
    public function createsMappingWithUserPrecedence() {
        $configuration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'mapper' => [
                        'className' => TestMapperFixture::class,
                    ],
                    'mapping' => [
                        'properties' => [
                            'existingKey' => 'existingValue',
                            'overrideKey' => 'overrideValue',
                        ]
                    ]
                ],
            ],
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $mapping = $this->configurationManager->getMapping('pages');

        $this->assertEquals('existingValue', $mapping['pages']['properties']['existingKey']);
        $this->assertEquals('overrideValue', $mapping['pages']['properties']['overrideKey']);
        $this->assertEquals('newMapperValue', $mapping['pages']['properties']['newKey']);
    }

    /**
     * @test
     */
    public function createsUpdateConfiguration() {
        $configuration = [
            'pages' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'pages',
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'table' => 'pagestable',
                            'subCollectors' => [
                                'myType' => [
                                    'className' => TestDataCollectorFixture::class,
                                    'config' => [
                                        'table' => 'contenttable',
                                        'field' => 'content',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'extensioncontent' => [
                'className' => TestIndexerFixture::class,
                'config' => [
                    'type' => 'extensiontype',
                    'collector' => [
                        'className' => TestDataCollectorFixture::class,
                        'config' => [
                            'table' => 'extensiontable',
                            'subCollectors' => [
                                'myType' => [
                                    'className' => TestDataCollectorFixture::class,
                                    'config' => [
                                        'table' => 'contenttable',
                                        'field' => 'content'
                                    ]
                                ],
                                'myType2' => [
                                    'className' => TestDataCollectorFixture::class,
                                    'config' => [
                                        'table' => 'othertable',
                                        'field' => 'othercontent'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],            
            ]
        ];
        $this->extconfService->getIndexerConfiguration()->willReturn($configuration);

        $expectedUpdateConfiguration = [
            'database' => [
                'toplevel' => [
                    'pagestable' => 'pages',
                    'extensiontable' => 'extensiontype'
                ],
                'sublevel' => [
                    'contenttable' => [
                        'pages' => 'content',
                        'extensiontype' => 'content'
                    ],
                    'othertable' => [
                        'extensiontype' => 'othercontent'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedUpdateConfiguration, $this->configurationManager->getUpdateConfiguration());  
    }

    
}
