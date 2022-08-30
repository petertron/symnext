<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE stylesheet [
    <!ENTITY regex1 "/^-?(?:\d+(?:\.\d+)?|\.\d+)$/i" >
    <!ENTITY regex_email "/^\w(?:\.?[\w+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i" >
]>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

	<xsl:variable name="page-title" select="'Edit'"/>
	<xsl:variable name="body-id" select="'x'"/>
	<xsl:variable name="route_num" select="/*/params/route_num"/>
    <xsl:variable name="form" select="document(/data/params/routes_file)/routes/*[position()=$route_num]"/>

    <!-- Strings for the page-->
    <xsl:param name="legend.essentials">Essentials</xsl:param>
    <xsl:param name="label.name">Name</xsl:param>
    <xsl:param name="label.path">Path</xsl:param>
	<xsl:param name="label.view">View</xsl:param>
	<xsl:param name="label.method">Request Method</xsl:param>
	<xsl:param name="label.allow-filtering">Allow filtering of section entries</xsl:param>
	<xsl:param name="legend.filters">Filters</xsl:param>
    <xsl:param name="label.make-required">Make this a required entry</xsl:param>
    <xsl:param name="label.display-entries">Display in entries table</xsl:param>

    <sn:method-options>
        <option value="any">Any</option>
        <option value="get">Get</option>
        <option value="post">Post</option>
    </sn:method-options>

	<xsl:template name="context.subheading">
        <xsl:choose>
            <xsl:when test="/*/params/route_num">
                <xsl:value-of select="/*/params/route_num"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="sn:__('New Route')"/>
            </xsl:otherwise>
        </xsl:choose>
	</xsl:template>

	<xsl:template name="context-actions">
	</xsl:template>

    <xsl:template match="/data" mode="contents">
        <xsl:variable name="view-options">
            <xsl:if test="count(views/view) > 0">
                <xsl:for-each select="views/view">
                    <xsl:copy-of select="form:option(
                        document(.)/*/meta/name,
                        document(.)/*/meta/handle,
                        string($form/@view))"/>
                </xsl:for-each>
            </xsl:if>
        </xsl:variable>
        <xsl:variable name="method">
            <xsl:choose>
                <xsl:when test="$form/@method">
                    <xsl:value-of select="$form/@method"/>
                </xsl:when>
                <xsl:otherwise>any</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
		<fieldset class="settings">
			<legend><xsl:value-of select="$legend.essentials"/></legend>
            <div class="column">
                <xsl:copy-of select="form:input(
                    $label.path, 'route[path]', $form/@path)"/>
            </div>
            <div class="two columns">
                <div class="column">
                    <xsl:copy-of select="form:select(
                        $label.view, 'route[view]', $view-options)"/>
                </div>
                <xsl:variable name="method-options">
                    <xsl:for-each select="document('')/*/sn:method-options/option">
                        <xsl:copy-of select="form:option(., @value, string($method))"/>
                    </xsl:for-each>
                </xsl:variable>
                <div class="column">
                    <xsl:copy-of select="form:select(
                        $label.method, 'route[method]', $method-options)"/>
                </div>
            </div>
		</fieldset>

		<fieldset class="settings">
            <legend><xsl:value-of select="$legend.filters"/></legend>
            <p class="help toggle"><a class="expand">Expand All</a><br /><a class="collapse">Collapse All</a><br /></p>
            <duplicator-frame class="frame duplicator collapsible orderable" id="fields-duplicator">
                <div data-add="Add field" data-remove="Remove field">
                    <xsl:apply-templates select="$form/filter"/>
                </div>
                <fieldset class="apply">
                    <button class="constructor" type="button">Add Filter</button>
                </fieldset>
            </duplicator-frame>
		</fieldset>

        <div class="actions">
            <input name="action[save]" type="submit" value="Save Changes" accesskey="s"/>
            <button name="action[delete]" class="button confirm delete" title="Delete this section" type="submit" accesskey="d" data-message="Are you sure you want to delete this section?">Delete</button>
            <input name="action[timestamp]" type="hidden" value="2022-04-16T00:19:49+01:00"/>
            <input name="action[ignore-timestamp]" type="checkbox" value="yes" class="irrelevant"/>
        </div>

        <template id="default-field">
            <xsl:call-template name="filter"/>
        </template>

    </xsl:template>

    <xsl:template name="filter" match="filter">
        <duplicator-field class="field orderable collapsible" data-type="">
            <xsl:variable name="name">
                <xsl:choose>
                    <xsl:when test="@param"><xsl:value-of select="@param"/></xsl:when>
                    <xsl:otherwise>New Filter</xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <header class="frame-header" data-default-name="Unnamed Filter">
                <h4><strong><xsl:value-of select="$name"/></strong> <span class="type"><xsl:value-of select="''"/></span></h4><a class="destructor">Remove field</a>
            </header>
            <div class="content">
                <div>
                    <div class="two columns">
                        <div class="column">
                            <xsl:copy-of select="form:input(
                                'Parameter Name',
                                concat('route[filters][', position() - 1, '][param]'),
                                @param)"/>
                        </div>
                        <div class="column">
                            <xsl:copy-of select="form:input(
                                'Regular Expression',
                                concat('route[filters][', position() - 1, '][regex]'),
                                @regex)"/>
                        </div>
                    </div>
                </div>
            </div>
        </duplicator-field>
    </xsl:template>

</xsl:stylesheet>
