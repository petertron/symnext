<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:exsl="http://exslt.org/common"
    xmlns:func="http://exslt.org/functions"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="exsl func form"
    exclude-result-prefixes="xsl exsl func form sn">

    <xsl:param name="t-with-selected">With Selected...</xsl:param>
    <xsl:param name="t-apply">Apply</xsl:param>

    <!-- Input field -->

    <!--<func:function name="form:input">
    	<xsl:param name="label"/>
    	<xsl:param name="key"/>
    	<xsl:param name="value"/>
        <xsl:param name="type" select="'text'"/>
        <xsl:param name="required" select="'yes'"/>
        <xsl:param name="error"/>
        <func:result>
            <xsl:call-template name="form-all">
                <xsl:with-param name="core">
                    <label><xsl:value-of select="$label"/>
                        <input type="{$type}" name="{$key}" value="{$value}"/>
                    </label>
                </xsl:with-param>
                <xsl:with-param name="key" select="$key"/>
            </xsl:call-template>
        </func:result>
    </func:function>-->

    <func:function name="form:input">
    	<xsl:param name="label"/>
    	<xsl:param name="key"/>
    	<xsl:param name="value"/>
        <xsl:param name="type" select="'text'"/>
        <xsl:param name="required" select="'yes'"/>
        <xsl:param name="error"/>
        <xsl:variable name="inner">
            <label><xsl:value-of select="$label"/>
                <input type="{$type}" name="{$key}" value="{$value}"/>
            </label>
        </xsl:variable>
        <func:result>
            <xsl:choose>
                <xsl:when test="$error">
                    <div class="invalid">
                        <xsl:copy-of select="$inner"/>
                        <p><xsl:value-of select="$error"/></p>
                    </div>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:copy-of select="$inner"/>
                </xsl:otherwise>
            </xsl:choose>
        </func:result>
    </func:function>
 <!--select="/data/form/*[name()=$key]"/> -->

    <!-- Checkbox -->

	<func:function name="form:checkbox">
    	<xsl:param name="label"/>
    	<xsl:param name="key"/>
    	<xsl:param name="value"/>
    	<func:result>
            <!--<label class="column">-->
            <label>
                <input name="{$key}" value="no" type="hidden"/>
                <input name="{$key}" value="yes" type="checkbox">
                    <xsl:if test="$value='yes'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </input>
                <xsl:value-of select="$label"/>
            </label>
        </func:result>
	</func:function>

	<xsl:template name="form.checkbox">
    	<xsl:param name="label"/>
    	<xsl:param name="key"/>
    	<xsl:param name="value"/>
        <label>
            <input name="{$key}" value="no" type="hidden"/>
            <input name="{$key}" value="yes" type="checkbox">
                <xsl:if test="$value='yes'">
                    <xsl:attribute name="checked">checked</xsl:attribute>
                </xsl:if>
            </input>
            <xsl:value-of select="$label"/>
        </label>
	</xsl:template>


	<!-- Select box -->

    <func:function name="form:select">
        <xsl:param name="label"/>
        <xsl:param name="key"/>
        <xsl:param name="options"><option>None</option></xsl:param>
        <xsl:param name="multiple"/>
        <func:result>
            <label><xsl:value-of select="$label"/>
                <select name="{$key}">
                    <xsl:if test="$multiple">
                        <xsl:attribute name="multiple">multiple</xsl:attribute>
                    </xsl:if>
                    <xsl:copy-of select="$options"/>
                </select>
            </label>
        </func:result>
    </func:function>

    <func:function name="form:option">
        <xsl:param name="text"/>
        <xsl:param name="value"/>
        <xsl:param name="selected-value"/>
        <func:result>
            <option value="{$value}">
                <xsl:if test="(exsl:object-type($selected-value) = 'node-set' and $selected-value/*[text() = $value]) or (exsl:object-type($selected-value) = 'string' and $selected-value = $value)">
                    <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:value-of select="$text"/>
            </option>
        </func:result>
    </func:function>

	<xsl:template name="form-all">
		<xsl:param name="core"/>
            <div>
                <xsl:copy-of select="$core"/>
            </div>
	</xsl:template>

    <xsl:template name="form-apply">
        <xsl:param name="options"/>
        <fieldset class="apply">
            <div>
                <label class="accessible" for="with-selected">
                    <xsl:value-of select="t-actions"/>
                </label>
                <select name="with-selected" id="with-selected">
                    <option value=""><xsl:value-of select="$t-with-selected"/></option>
                    <!--<xsl:copy-of select="apply-options/*" />-->
                    <xsl:copy-of select="$options"/>
                </select>
            </div>
            <button name="action[apply]" type="submit"><xsl:value-of select="$t-apply"/></button>
        </fieldset>
    </xsl:template>

    <xsl:template match="field[@type='select']|field[@type='multi-select']">
        <xsl:variable name="handle" select="handle"/>
        <div>
            <label><xsl:value-of select="label"/>
                <select name="{name}">
                    <xsl:if test="@type='multi-select'">
                        <xsl:attribute name="multiple">multiple</xsl:attribute>
                    </xsl:if>
                    <xsl:for-each select="$meta/*[name()=$handle]/option">
                        <option value="{@value}">
                            <xsl:if test="@selected='yes'">
                                <xsl:attribute name="selected">selected</xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="."/>
                        </option>
                    </xsl:for-each>
                </select>
            </label>
        </div>
    </xsl:template>

</xsl:stylesheet>
