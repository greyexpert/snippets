<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package snippets.classes
 */
class SNIPPETS_CLASS_PhotoBridge
{
    
    const WIDGET_NAME = "photo_albums";
    
    /**
     * Class instance
     *
     * @var SNIPPETS_CLASS_PhotoBridge
     */
    protected static $classInstance;

    /**
     * Returns class instance
     *
     * @return SNIPPETS_CLASS_PhotoBridge
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    protected function __construct()
    {
        
    }
    
    public function isActive()
    {
        return OW::getPluginManager()->isPluginActive("photo");
    }
    
    public function collectSnippets( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();
        
        if ( $params["entityType"] != SNIPPETS_CLASS_EventHandler::ENTITY_TYPE_USER )
        {
            return;
        }

        $showEmpty = !$params["hideEmpty"];
        $userId = $params["entityId"];
        $preview = $params["preview"];
        
        $snippet = new SNIPPETS_CMP_Snippet(self::WIDGET_NAME, $userId);
        
        if ( $preview )
        {
            $snippet->setLabel($language->text("snippets", "snippet_photo_preview"));
            $snippet->setIconClass("ow_ic_picture");
            $event->add($snippet);
            
            return;
        }
        
        // Privacy check
        $eventParams =  array(
            'action' => "photo_view_album",
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );
        
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $exception )
        {
            return;
        }
        
        $data = OW::getEventManager()->call("photo.entity_albums_find", array(
            "entityType" => "user",
            "entityId" => $userId,
            "userId" => $userId,
            "offset" => 0,
            "limit" => 3
        ));
        
        $total = OW::getEventManager()->call("photo.entity_photos_count", array(
            "entityType" => "user",
            "entityId" => $userId,
            "userId" => $userId
        ));

        $url = OW::getRouter()->urlForRoute("photo_user_albums", array(
            "user" => BOL_UserService::getInstance()->getUserName($userId)
        ));

        $snippet->setLabel($language->text("snippets", "snippet_photos", array(
            "count" => '<span class="ow_txt_value">' . $total . '</span>'
        )));

        $snippet->setUrl($url);
        $isEmpty = empty($data) || empty($data["albums"]);

        if ( !$isEmpty )
        {
            $images = array();
            $snippetData = array();
            foreach ( $data["albums"] as $album )
            {
                $images[] = $album["coverImage"];
                $snippetData[$album["id"]] = $album["coverImage"];
            }

            $snippet->setData($snippetData);
            $snippet->setImages($images);
        }

        if (!$isEmpty || $showEmpty) {
            $event->add($snippet);
        }
    }
    
    public function init()
    {
        if ( !$this->isActive() )
        {
            return;
        }
        
        OW::getEventManager()->bind(SNIPPETS_CLASS_EventHandler::EVENT_COLLECT_SNIPPETS, array($this, "collectSnippets"));
    }
}