<?php

namespace tests\web\news;

use Yii;
use luya\helpers\Url;

class UrlRuleTest extends \tests\web\Base
{
    public $urlRules = [
        ['pattern' => 'news/detail/<id:\d+>/<title:[a-zA-Z0-9\-]+>/', 'route' => 'news/default/detail'],
        ['pattern' => 'news/global-test/<id:\d+>/', 'route' => 'news/test', 'composition' => [
                'fr' => 'news/french-test/<id:\d+>/',
                'en' => 'news/english-test/<id:\d+>/',
                'de' => 'news/deutsch-test/<id:\d+>/',
            ],
        ],
    ];

    public function setUp()
    {
        parent::setUp();
        Yii::$app->request->baseUrl = '';
        Yii::$app->request->scriptUrl = '';
        Yii::$app->urlManager->addRules($this->urlRules);
        Yii::$app->composition->setKey('langShortCode', 'de');
    }

    public function testComposition()
    {
        Yii::$app->composition->hidden = true;
        $full = Yii::$app->composition->getFull();
        $this->assertEquals('', $full);

        Yii::$app->composition->hidden = false;
        $full = Yii::$app->composition->getFull();
        $this->assertEquals('de/', $full);
    }

    public function testCompositionUrls()
    {
        Yii::$app->composition->hidden = false;
        // fr
        Yii::$app->composition->setKey('langShortCode', 'fr');
        $this->assertEquals('/fr/news/french-test/1', Url::toManager('news/test', ['id' => 1]));
        // en
        Yii::$app->composition->setKey('langShortCode', 'en');
        $this->assertEquals('/en/news/english-test/1', Url::toManager('news/test', ['id' => 1]));
        // de
        Yii::$app->composition->setKey('langShortCode', 'de');
        $this->assertEquals('/de/news/deutsch-test/1', Url::toManager('news/test', ['id' => 1]));
        // ru (composition not set, use global default pattern)
        Yii::$app->composition->setKey('langShortCode', 'ru');
        $this->assertEquals('/ru/news/global-test/1', Url::toManager('news/test', ['id' => 1]));

        // composition is hidden, so url rules automaticcaly retursn generic default global pattern
        Yii::$app->composition->hidden = true;
        // fr (will not work cause hidden)
        Yii::$app->composition->setKey('langShortCode', 'fr');
        $this->assertEquals('/news/global-test/1', Url::toManager('news/test', ['id' => 1]));
    }

    public function testBasicUrls()
    {
        Yii::$app->composition->hidden = true;
        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar']);
        $this->assertEquals('/news/1/foo-bar', $url);

        Yii::$app->composition->hidden = false;
        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar']);
        $this->assertEquals('/de/news/1/foo-bar', $url);

        Yii::$app->composition->hidden = true;
        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar', 'pa' => 'ram']);
        $this->assertEquals('/news/1/foo-bar?pa=ram', $url);

        Yii::$app->composition->hidden = false;
        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar', 'pa' => 'ram']);
        $this->assertEquals('/de/news/1/foo-bar?pa=ram', $url);
    }

    public function testModuleContextUrls()
    {
        Yii::$app->urlManager->setContextNavItemId(1);

        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar']);
        $this->assertEquals('/1/foo-bar', $url);

        Yii::$app->urlManager->setContextNavItemId(2);

        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar']);
        $this->assertEquals('/de/page-2/1/foo-bar', $url);

        Yii::$app->urlManager->setContextNavItemId(2);

        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar', 'pa' => 'ram']);
        $this->assertEquals('/de/page-2/1/foo-bar?pa=ram', $url);

        Yii::$app->urlManager->setContextNavItemId(1);

        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'page-2-news-title', 'news' => 'page']);
        $this->assertEquals('/1/page-2-news-title?news=page', $url);
    }

    public function testModuleUrls()
    {
        Yii::$app->urlManager->resetContext();
        Yii::$app->composition->hidden = false;
        Yii::$app->getModule('news')->setContext('cms');
        Yii::$app->getModule('news')->setContextOptions(['navItemId' => 1]);
        $url = Url::toManager('news/default/detail', ['id' => 1, 'title' => 'foo-bar']);
        $this->assertEquals('/1/foo-bar', $url);
    }
}
