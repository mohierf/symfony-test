// It is mandatory to use the path because the module includes an index.js file
// that exports the src and not the dist version!
// -----
require('jsoneditor/dist/jsoneditor.css');
require('jsoneditor/dist/jsoneditor.js');
// import JSONEditor from 'jsoneditor/dist/jsoneditor.js';


/*
 Based on https://github.com/josdejong/jsoneditor
 */

$(document).ready(function (){
    // **********
    // JSON editor
    // **********
    console.log('json editor page!')

    // Get the current JSON schema from a form field
    let json_name = $('#json_schema_name').val();
    let json_content = JSON.parse($('#json_schema_content').val());
    console.log(json_name, json_content)

    let schema = {
        // "$schema": "http://json-schema.org/draft-07/schema#",
        // "$id": "http://json-schema.org/draft-07/schema#",
        "title": "Core schema meta-schema",
        "definitions": {
            "schemaArray": {
                "type": "array",
                "minItems": 1,
                "items": { "$ref": "#" }
            },
            "nonNegativeInteger": {
                "type": "integer",
                "minimum": 0
            },
            "nonNegativeIntegerDefault0": {
                "allOf": [
                    { "$ref": "#/definitions/nonNegativeInteger" },
                    { "default": 0 }
                ]
            },
            "simpleTypes": {
                "enum": [
                    "array",
                    "boolean",
                    "integer",
                    "null",
                    "number",
                    "object",
                    "string"
                ]
            },
            "stringArray": {
                "type": "array",
                "items": { "type": "string" },
                "uniqueItems": true,
                "default": []
            }
        },
        "type": ["object", "boolean"],
        "properties": {
            "$id": {
                "type": "string",
                "format": "uri-reference"
            },
            "$schema": {
                "type": "string",
                "format": "uri"
            },
            "$ref": {
                "type": "string",
                "format": "uri-reference"
            },
            "$comment": {
                "type": "string"
            },
            "title": {
                "type": "string"
            },
            "description": {
                "type": "string"
            },
            "default": true,
            "readOnly": {
                "type": "boolean",
                "default": false
            },
            "examples": {
                "type": "array",
                "items": true
            },
            "multipleOf": {
                "type": "number",
                "exclusiveMinimum": 0
            },
            "maximum": {
                "type": "number"
            },
            "exclusiveMaximum": {
                "type": "number"
            },
            "minimum": {
                "type": "number"
            },
            "exclusiveMinimum": {
                "type": "number"
            },
            "maxLength": { "$ref": "#/definitions/nonNegativeInteger" },
            "minLength": { "$ref": "#/definitions/nonNegativeIntegerDefault0" },
            "pattern": {
                "type": "string",
                "format": "regex"
            },
            "additionalItems": { "$ref": "#" },
            "items": {
                "anyOf": [
                    { "$ref": "#" },
                    { "$ref": "#/definitions/schemaArray" }
                ],
                "default": true
            },
            "maxItems": { "$ref": "#/definitions/nonNegativeInteger" },
            "minItems": { "$ref": "#/definitions/nonNegativeIntegerDefault0" },
            "uniqueItems": {
                "type": "boolean",
                "default": false
            },
            "contains": { "$ref": "#" },
            "maxProperties": { "$ref": "#/definitions/nonNegativeInteger" },
            "minProperties": { "$ref": "#/definitions/nonNegativeIntegerDefault0" },
            "required": { "$ref": "#/definitions/stringArray" },
            "additionalProperties": { "$ref": "#" },
            "definitions": {
                "type": "object",
                "additionalProperties": { "$ref": "#" },
                "default": {}
            },
            "properties": {
                "type": "object",
                "additionalProperties": { "$ref": "#" },
                "default": {}
            },
            "patternProperties": {
                "type": "object",
                "additionalProperties": { "$ref": "#" },
                "propertyNames": { "format": "regex" },
                "default": {}
            },
            "dependencies": {
                "type": "object",
                "additionalProperties": {
                    "anyOf": [
                        { "$ref": "#" },
                        { "$ref": "#/definitions/stringArray" }
                    ]
                }
            },
            "propertyNames": { "$ref": "#" },
            "const": true,
            "enum": {
                "type": "array",
                "items": true,
                "minItems": 1,
                "uniqueItems": true
            },
            "type": {
                "anyOf": [
                    { "$ref": "#/definitions/simpleTypes" },
                    {
                        "type": "array",
                        "items": { "$ref": "#/definitions/simpleTypes" },
                        "minItems": 1,
                        "uniqueItems": true
                    }
                ]
            },
            "format": { "type": "string" },
            "contentMediaType": { "type": "string" },
            "contentEncoding": { "type": "string" },
            "if": { "$ref": "#" },
            "then": { "$ref": "#" },
            "else": { "$ref": "#" },
            "allOf": { "$ref": "#/definitions/schemaArray" },
            "anyOf": { "$ref": "#/definitions/schemaArray" },
            "oneOf": { "$ref": "#/definitions/schemaArray" },
            "not": { "$ref": "#" }
        },
        "default": true
    };
    let schema_refs = null;

    // create the editor
    var container = document.getElementById("jsoneditor");

    var options = {
        name: json_name,
        schema: schema,
        schemaRefs: schema_refs,
        mode: 'tree',
        // modes: ['tree', 'view', 'form', 'code', 'text'],
        modes: ['tree', 'text'],
        onError: function (error) {
            alert("JsonEditor error!\n" + error);
        },
        onChange: function () {
            try {
                var edited_json = myJsonEditor.get();
            } catch (e) {
                return;
            }

            let valid_json = myJsonEditor.validateSchema(edited_json);
            if (valid_json) {
                $('#json_schema_json_content').text(JSON.stringify(edited_json));
            } else {
                let errors = myJsonEditor.validateSchema.errors;
                let errorMessage = '';
                for (var i = 0; i < errors.length; i++) {
                    errorMessage += errors[i].schemaPath + ": " + errors[i].message + "\n";
                }
                // todo: as of now, nothing special to do with the error message
            }
        },
    };

    // Instantiate the editor
    let myJsonEditor = new JSONEditor(container, options, json_content);
});
