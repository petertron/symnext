<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
	version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

    <xsl:variable name="page-title">Extensions</xsl:variable>

    <xsl:variable name="body-id">system-extensions</xsl:variable>

	<xsl:template name="context.subheading">
        <xsl:value-of select="sn:__('Extensions')"/>
	</xsl:template>

	<xsl:template match="*" mode="context.actions">
	</xsl:template>

    <xsl:template match="/data" mode="contents">
        <table class="selectable" role="directory" aria-labelledby="symnext-subheading">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Version</th>
                    <th scope="col">Status</th>
                    <th scope="col">Links</th>
                    <th scope="col">Authors</th>
                </tr>
            </thead>
            <tbody>
                <xsl:choose>
                    <xsl:when test="count(extensions/extension) > 0">
                        <xsl:apply-templates select="extensions/extension"/>
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

    <xsl:template match="extension">
        <tr>
            <td>
                <xsl:value-of select="name"/>
                <label class="accessible" for="section-{@handle}">Select Section <xsl:value-of select="name"/></label>
                <input name="items[{@handle}]" type="checkbox" value="2021-12-16T21:30:31+00:00" id="extension-{name}"/>
            </td>
            <td><xsl:value-of select="version"/></td>
            <td></td>
            <td></td>
            <td>
                <xsl:for-each select="authors/author">
                    <xsl:choose>
                        <xsl:when test="homepage">
                            <a href="{homepage}"><xsl:value-of select="name"/></a>
                        </xsl:when>
                        <xsl:otherwise><xsl:value-of select="name"/></xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
            </td>
        </tr>
    </xsl:template>

    <xsl:template name="none-found">
        <tr class="odd">
            <td class="inactive" colspan="3">None found.</td>
        </tr>
	</xsl:template>

</xsl:stylesheet>
