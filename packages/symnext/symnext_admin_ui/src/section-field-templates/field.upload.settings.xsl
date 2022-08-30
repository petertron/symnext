<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:exsl="http://exslt.org/common"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldUpload']" mode="section-field-primary">
        <xsl:param name="index"/>
        <xsl:variable name="destination" select="string(destination)"/>
        <xsl:variable name="options">
            <xsl:for-each select="$workspace_dirs/*">
                <xsl:copy-of select="form:option(., ., $destination)"/>
            </xsl:for-each>
        </xsl:variable>
        <div class="two columns">
            <div class="secondary column">
                <xsl:copy-of select="form:select(
                    sn:__('Destination Directory'),
                    concat('fields[', $index, '][destination]'),
                    $options
                )"/>
            </div>
            <div class="primary column">
                <label><xsl:value-of select="sn:__('Validation Rule')"/>
                    <i>Optional</i>
                    <input name="fields[{$index}][validator]" value="{validation}" type="text" data-role="validation-rule"/>
                </label>
                <ul is="selector-list" class="tags single" boundary="duplicator-field" target="input[data-role=validation-rule]">
                    <xsl:for-each select="$validators/upload/rule">
                        <li data-output="{@pattern}"><xsl:value-of select="@for"/></li>
                    </xsl:for-each>
                </ul>
            </div>
        </div>
	</xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldUpload']" mode="section-field-secondary">
        <xsl:param name="index"/>
        <div class="two columns">
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
