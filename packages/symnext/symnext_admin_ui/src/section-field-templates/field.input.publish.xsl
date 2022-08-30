<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

	<xsl:template match="field[@class='Symnext\SectionFields\Input']" mode="publish">
        <xsl:param name="index"/>
        <label class="column"><xsl:value-of select="">
            <i>Optional</i>
            <input name="fields[]" type="text" value="$value"/>
        </label>
        <ul class="tags singular" data-interactive="data-interactive">
            <li data-regex="{$regex-number}">number</li>
            <li data-regex="{$regex-email}">email</li>
            <li data-regex="{$regex-uri}">URI</li>
        </ul>
	</xsl:template>
	<xsl:template match="field[@class='Symnext\SectionFields\Input']" mode="publish-field">
        <input name="fields[]" type="text" value="$value"/>
    </xsl:template>

    <xsl:template match="field[@class='Symnext\SectionFields\Input'" mode="field-extra">
        <ul class="tags singular" data-interactive="data-interactive">
            <li data-regex="{$regex-number}">number</li>
            <li data-regex="{$regex-email}">email</li>
            <li data-regex="{$regex-uri}">URI</li>
        </ul>
	</xsl:template>
	<xsl:template match="field[@class='input']" mode="publish">
        <xsl:param name="index"/>
        <label class="column"><xsl:value-of select="">
            <i>Optional</i>
            <input name="fields[]" type="text" value="$value"/>
        </label>
        <ul class="tags singular" data-interactive="data-interactive">
            <li data-regex="{$regex-number}">number</li>
            <li data-regex="{$regex-email}">email</li>
            <li data-regex="{$regex-uri}">URI</li>
        </ul>
	</xsl:template>
</xsl:stylesheet>
