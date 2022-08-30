<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
	version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

    <xsl:variable name="page-title">Routes</xsl:variable>

    <xsl:variable name="body-id">blueprints-sections</xsl:variable>

    <xsl:variable name="routes" select="document(/data/routes)/routes"/>

    <xsl:variable name="views" select="/data/views"/>

    <xsl:template name="context.subheading">
        <xsl:value-of select="sn:__('Routes')"/>
    </xsl:template>

	<xsl:template name="context-actions">
		<a class="button create" href="{$admin-url}/blueprints/sections/new">Create New</a>
		<xsl:copy-of select="//context/actions"/>
		<!--<xsl:apply-templates select="//context/actions"/>-->
	</xsl:template>

    <xsl:template match="/data" mode="contents">
        <table class="selectable" role="directory" aria-labelledby="symnext-subheading">
            <thead>
                <tr>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                    <th scope="col">Action</th>
                    <th scope="col">Request Method</th>
                </tr>
            </thead>
            <tbody>
                <xsl:choose>
                    <xsl:when test="count($routes/*[self::route or self::redirect]) > 0">
                        <xsl:apply-templates select="$routes/*[self::route or self::redirect]"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:call-template name="none-found"/>
                    </xsl:otherwise>
                </xsl:choose>
            </tbody>
        </table>
        <p id="version">Symnext <xsl:value-of select="params/symnext-version"/></p>
        <div class="actions">
            <xsl:call-template name="form-apply">
                <xsl:with-param name="options">
                    <option value="delete" data-alert="{$sure-delete}">Delete</option>
                </xsl:with-param>
            </xsl:call-template>
        </div>
    </xsl:template>

    <xsl:template match="routes/route|routes/redirect">
        <xsl:variable name="view-handle" select="@view"/>
        <tr>
            <td>
                <a href="{concat($admin-url, '/blueprints/routes/edit/', position())}" class="content"><xsl:value-of select="@path"/></a>
                <label class="accessible" for="route-{position()}">Select Route <xsl:value-of select="position()"/></label>
                <input name="items[{position}]" type="checkbox"  value="2021-12-16T21:30:31+00:00" id="route-{position()}"/>
            </td>
            <td><xsl:copy-of select="document($views/view[@handle=$view-handle])/*/meta/name"/></td>
            <td>
                <xsl:choose>
                    <xsl:when test="name()='route'">Route</xsl:when>
                    <xsl:when test="name()='redirect'">Redirect</xsl:when>
                </xsl:choose>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@method='any' or not(@method)">Any</xsl:when>
                    <xsl:when test="@method='get'">Get</xsl:when>
                    <xsl:when test="@method='post'">Post</xsl:when>
                </xsl:choose>
            </td>
        </tr>
    </xsl:template>

    <xsl:template name="none-found">
        <tr class="odd">
            <td class="inactive" colspan="3">None found.</td>
        </tr>
	</xsl:template>

</xsl:stylesheet>
