<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    xmlns:form="symnext:form"
    exclude-result-prefixes="sn form">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldDate']" mode="section-field-primary">
        <xsl:param name="index"/>
        <label><xsl:value-of select="sn:__('Default Date')"/><i>Optional, accepts absolute or relative dates</i>
            <input name="fields[{$index}][default_date]" value="{default_date}" type="text"/>
        </label>
	</xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldDate']" mode="section-field-secondary">
        <xsl:param name="index"/>
        <div class="two columns">
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    sn:__('Show time'),
                    concat('fields[', $index, '][time]'),
                    required)"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    sn:__('Show calendar'),
                    concat('fields[', $index, '][calendar]'),
                    show_column)"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    $label.make-required,
                    concat('fields[', $index, '][required]'),
                    required)"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    $label.display-entries,
                    concat('fields[', $index, '][show_column]'),
                    show_column)"/>
            </div>
        </div>
	</xsl:template>

</xsl:stylesheet>
