<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:func="http://exslt.org/functions"
    xmlns:exsl="http://exslt.org/common"
    xmlns:set="http://exslt.org/sets"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="func set sn"
    exclude-result-prefixes="func exsl set">

    <xsl:import href="./form-field-templates.xsl"/>

    <xsl:output
        method="html"
        doctype-system="about:legacy-compat"
        omit-xml-declaration="yes"
        encoding="UTF-8" />

    <xsl:variable name="xlate" select="document('./translation.xml')/*"/>

	<xsl:variable name="admin-url" select="//params/admin-url"/>

    <xsl:param name="sure-delete">You sure?</xsl:param>

    <xsl:template match="/">
        <html>
            <head>
                <title><xsl:value-of select="concat($page-title, ' – Symnext')" /></title>
                <!--<link rel="stylesheet" type="text/css" href="/symphony.min.css"/>-->
                <link rel="stylesheet" type="text/css" href="/css/symphony.css"/>
                <link rel="stylesheet" type="text/css" href="/css/admin.css"/>
                <link rel="stylesheet" type="text/css" href="/css/symphony.forms.css"/>
                <link rel="stylesheet" type="text/css" href="/css/symphony.frames.css"/>
                <link rel="stylesheet" type="text/css" href="/css/symphony.grids.css"/>
                <link rel="stylesheet" type="text/css" href="/css/symphony.tables.css"/>
                <link rel="stylesheet" type="text/css" href="/extra-styles.css"/>
                <script type="text/javascript" src="/form.js"></script>
                <script type="text/javascript" src="/selectable.js" defer="defer"></script>
                <script type="text/javascript" src="/duplicator.js"></script>
                <style>duplicator-frame, duplicator-field {display: block;}</style>
                <xsl:copy-of select="/data/add-to-head/*"/>
            </head>
            <body id="{$body-id}">
                <xsl:choose>
                    <xsl:when test="page/type = 'single'">
                        <xsl:attribute name="class">page-single</xsl:attribute>
                        <xsl:attribute name="data-action">
                            <xsl:value-of select="page/action"/>
                        </xsl:attribute>
                        <xsl:attribute name="data-handle"><xsl:value-of select="page/handle"/></xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="class">page-index</xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
                <div id="wrapper">
                    <header id="header">
                        <h1>Symnext CMS</h1>
                        <ul id="session">
                        	<li>
                                <a href="{$admin-url}/system/authors/edit/1/">Peter S</a>
                            </li>
                            <li>
                                <a href="{$admin-url}/logout/" accesskey="l">Log out</a>
                            </li>
                        </ul>
                        <nav id="nav" class="wide" role="navigation">
                            <ul class="content" role="menubar">
                                <xsl:apply-templates select="/data/navigation/content/*"/>
                            </ul>
                            <ul class="structure" role="menubar">
                                <xsl:apply-templates select="/data/navigation/structure/*"/>
                            </ul>
                        </nav>
                    </header>
                    <main>
                        <div id="context">
                            <div id="breadcrumbs">
                                <xsl:if test="/*/context/breadcrumbs/crumb">
                                    <nav>
                                        <xsl:apply-templates select="/*/context/breadcrumbs/crumb"/>
                                    </nav>
                                </xsl:if>
                                <!--<h2><xsl:value-of select="/*/context/heading"/></h2>-->
                                <h2><xsl:call-template name="context.subheading"/></h2>
                            </div>
                            <ul class="actions">
                                <!--<xsl:call-template name="context.actions"/>-->
                                <xsl:apply-templates mode="context.actions"/>
                            </ul>
                        </div>
                        <div id="contents">
                            <form method="POST" action="" role="form">
                                <input type="hidden" name="xsrf" value="{/data/params/xsrf_token}"/>
                                <xsl:apply-templates select="/data" mode="contents"/>
                                <!--<div class="actions">
                                    <xsl:call-template name="bottom-actions"/>
                                </div>-->
                            </form>
                        </div>
                    </main>
                </div>
            </body>
        </html>
    </xsl:template>

    <!--<xsl:template match="nav_group">
        <xsl:variable name="group" select="."/>
        <li role="presentation" aria-haspopup="true"><xsl:value-of select="$group"/>
        <ul>
            <xsl:for-each select="$sections/section/meta[nav_group = $group]">
                <li role="menuitem"><a href="{$admin-url}/publish/{handle}"><xsl:value-of select="name"/></a></li>
            </xsl:for-each>
        </ul>
        </li>
    </xsl:template>-->

    <xsl:template match="navigation/*/*">
        <li role="presentation" aria-haspopup="true"><xsl:value-of select="@name"/>
            <ul role="menu">
                <xsl:for-each select="item">
                    <li role="menuitem">
                        <a href="{@href}"><xsl:value-of select="."/></a>
                    </li>
                </xsl:for-each>
            </ul>
        </li>
    </xsl:template>

    <xsl:template match="breadcrumbs/crumb">
        <p>
            <a href="{@link}"><xsl:value-of select="."/></a><span class="sep">&#8250;</span>
        </p>
    </xsl:template>

    <xsl:template match="*" mode="context.actions" prority="10">
    </xsl:template>

    <func:function name="sn:__">
        <xsl:param name="text"/>
        <func:result><xsl:value-of select="$text"/></func:result>
    </func:function>

</xsl:stylesheet>
