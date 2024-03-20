<?php

namespace Jubayed\Composer;

use Exception;
use Illuminate\Support\Str;

class Saas
{
   /**
    * url port
    *
    * @var int
    */
   protected $port = 80;

   /**
    * url host name
    *
    * @var string
    */
   protected $subdomain;

    /**
    * All registered website of this b2b application
    *
    * @var array
    */
    protected $websites = [];

   /**
    * website name
    *
    * @var string
    */
   protected $website;

   /**
    * url domain name
    *
    * @var string|null
    */
   protected $domain;

   /**
    * target module nae
    *
    * @var string|null
    */
   protected $module;

   /**
    * target cdn name
    *
    * @var string
    */
   protected $cdn_domain;

   /**
    * Get attributes 
    *
    * @var array
    */
    protected $attributes = [];

   /**
    * Get port
    *
    * @return string
    */
    public function getPort()
    {
        return $this->port;
    }

   /**
    * Get domain
    *
    * @return string
    */
    public function getDomain(): string
    {
        return $_SERVER['SERVER_NAME'];
    }

    /**
    * Get module
    *
    * @return string
    */
    public function getWebsite() : string
    {        
        return $_SERVER['WEBSITE_NAME'];
    }

   /**
    * Get module
    *
    * @return string
    */
    public function getModule() : string
    {        
        return $_SERVER['MODULE_NAME'];
    }

    /**
     * Get target cdn domain
     * 
     * @return mixed
     */
    public function getCdnDomain()
    {
        return $this->cdn_domain;
    }

    /**
     * Build website meta
     * 
     * @return void
     */
    public function build():void
    {
        $baseDir =  dirname(__DIR__, 4);
        $this->attributes = yaml_parse_file("{$baseDir}/websites/{$_SERVER['WEBSITE_NAME']}/website.yaml");
    }

    /**
     * Get database default connection name
     * 
     * @return string
     */
    public function dbDefault(): string
    {
        return $this->getModule() == 'site'? 'site': 'erp';
    }
    
    /**
     * Get website providers
     * 
     * @return array
     */
    public function providers(): array
    {
        $className = ucfirst($_SERVER["MODULE_NAME"]);

        $moduleProvider = "Modules\\{$className}\\Providers\\{$className}ServiceProvider";

        print_r($moduleProvider);
        exit( "149 jubayed/saas");

        if(!class_exists($moduleProvider)){
            throw new Exception("Moulde class not exist: {$className}ServiceProvider");
        }


        return [
            $moduleProvider
        ];
    }
}
