<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldInput']" mode="section-field-primary">
        <xsl:param name="index"/>
        <div class="column">
            <label>Validation Rule
                <i>Optional</i>
                <input name="fields[{$index}][validator]" type="text" data-role="validation-rule"/>
            </label>
            <ul is="selector-list" class="tags single" boundary="duplicator-field" target="input[data-role=validation-rule]">
                <xsl:for-each select="$validators/input/rule">
                    <li data-output="{@pattern}"><xsl:value-of select="@for"/></li>
                </xsl:for-each>
            </ul>
        </div>
        <div class="column">
        </div>
	</xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldInput']" mode="section-field-secondary">
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
