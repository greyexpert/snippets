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
class SNIPPETS_CLASS_ProtectedPhotoBridge
{

    /**
     * Class instance
     *
     * @var SNIPPETS_CLASS_FriendsBridge
     */
    protected static $classInstance;

    /**
     * Returns class instance
     *
     * @return SNIPPETS_CLASS_ProtectedPhotoBridge
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
        return OW::getPluginManager()->isPluginActive("protectedphotos");
    }

    protected function getLockImageUrl()
    {
        $plugin = OW::getPluginManager()->getPlugin('protectedphotos');

        return $plugin->getStaticUrl() . 'images/ic_pass_protected.svg';
    }

    public function beforeSnippetRender(OW_Event $event)
    {
        $params = $event->getParams();

        /**
         * @var $snippet SNIPPETS_CMP_Snippet
         */
        $snippet = $event->getData();

        if ($params["name"] !== SNIPPETS_CLASS_PhotoBridge::WIDGET_NAME)
        {
            return;
        }

        $isAdmin = OW::getUser()->isAuthorized('photo');

        if ($isAdmin)
        {
            return;
        }

        $albumCovers = $snippet->getData();
        $albumIds = array_keys($albumCovers);
        $access = PROTECTEDPHOTOS_BOL_Service::getInstance()->getAccessForUser(OW::getUser()->getId(), $albumIds);

        $images = array();
        foreach ($albumCovers as $albumId => $imageUrl)
        {
            $images[] = in_array($albumId, $access) ? $imageUrl : array(
                $this->getLockImageUrl(),
                "s-protected-photo"
            );
        }

        $snippet->setImages($images);
    }

    public function init()
    {
        if ( !$this->isActive() )
        {
            return;
        }

        OW::getEventManager()->bind(SNIPPETS_CLASS_EventHandler::EVENT_BEFORE_SNIPPET_RENDER, array($this, "beforeSnippetRender"));
    }
}