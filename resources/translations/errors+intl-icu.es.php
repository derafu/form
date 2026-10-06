<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    // Forms and loaders.
    'The form schema definition must be assigned if form data is not provided.' =>
        'La definición del esquema del formulario debe asignarse si no se entregan los datos del formulario.',
    'Invalid form name "{name}".' =>
        'Nombre de formulario inválido "{name}".',
    'Form definition "{name}" not found (extension "{extension}"). Searched in: {paths}' =>
        'No se encontró la definición del formulario "{name}" (extensión "{extension}"). Se buscó en: {paths}',
    'No loaders registered in ChainFormLoader.' =>
        'No hay cargadores registrados en ChainFormLoader.',
    'No loader could resolve form "{name}". Attempts: {attempts}' =>
        'Ningún cargador pudo resolver el formulario "{name}". Intentos: {attempts}',
    'Failed to parse JSON form file "{file}": {error}' =>
        'No se pudo interpretar el archivo JSON del formulario "{file}": {error}',
    'Form file "{file}" must decode to an array, got {type}.' =>
        'El archivo de formulario "{file}" debe decodificarse a un arreglo, se obtuvo {type}.',
    'Form file "{file}" must return a Closure or an array, got {type}.' =>
        'El archivo de formulario "{file}" debe retornar un Closure o un arreglo, se obtuvo {type}.',
    'YamlFormLoader requires "symfony/yaml". Run: composer require symfony/yaml' =>
        'YamlFormLoader requiere "symfony/yaml". Ejecuta: composer require symfony/yaml',
    'Failed to parse YAML form file "{file}": {error}' =>
        'No se pudo interpretar el archivo YAML del formulario "{file}": {error}',
    'Form file "{file}" must parse to an array, got {type}.' =>
        'El archivo de formulario "{file}" debe interpretarse como un arreglo, se obtuvo {type}.',

    // Choices shorthand.
    'choices shorthand does not support nested scopes: "{scope}". Use oneOf directly in the schema for nested properties.' =>
        'El atajo choices no soporta scopes anidados: "{scope}". Usa oneOf directamente en el esquema para propiedades anidadas.',
    'choices shorthand: property "{name}" not found in schema (referenced by scope "{scope}").' =>
        'Atajo choices: no se encontró la propiedad "{name}" en el esquema (referenciada por el scope "{scope}").',
    'choices shorthand is not supported for type "{type}" (property "{name}"). Use oneOf directly in the schema to preserve numeric const values.' =>
        'El atajo choices no está soportado para el tipo "{type}" (propiedad "{name}"). Usa oneOf directamente en el esquema para conservar los valores const numéricos.',
    'choices shorthand: property "{name}" items already has oneOf defined. Remove either the choices shorthand or the items.oneOf.' =>
        'Atajo choices: los items de la propiedad "{name}" ya tienen oneOf definido. Quita el atajo choices o items.oneOf.',
    'choices shorthand: property "{name}" already has oneOf defined. Remove either the choices shorthand or the schema oneOf.' =>
        'Atajo choices: la propiedad "{name}" ya tiene oneOf definido. Quita el atajo choices o el oneOf del esquema.',

    // UI schema.
    'Invalid UI schema type: {type}. Valid types are: {types}.' =>
        'Tipo de esquema de UI inválido: {type}. Los tipos válidos son: {types}.',
    'Invalid UI schema element type: {type}. Valid types are: {types}.' =>
        'Tipo de elemento de esquema de UI inválido: {type}. Los tipos válidos son: {types}.',
    'Invalid scope format: {scope}. Expected format: #/properties/path/to/property' =>
        'Formato de scope inválido: {scope}. Formato esperado: #/properties/ruta/a/la/propiedad',
    'A UiSchemaRule definition requires an "effect" key.' =>
        'Una definición de UiSchemaRule requiere la clave "effect".',
    'A UiSchemaRule definition requires a "condition" key.' =>
        'Una definición de UiSchemaRule requiere la clave "condition".',
    'A UiSchemaCondition definition requires a "scope" key.' =>
        'Una definición de UiSchemaCondition requiere la clave "scope".',
    'A UiSchemaCondition definition requires a "schema" key.' =>
        'Una definición de UiSchemaCondition requiere la clave "schema".',
    'A UiSchemaCompositeCondition definition requires a "type" key.' =>
        'Una definición de UiSchemaCompositeCondition requiere la clave "type".',
    'A UiSchemaCompositeCondition definition requires a non-empty "conditions" array.' =>
        'Una definición de UiSchemaCompositeCondition requiere un arreglo "conditions" no vacío.',
    'Invalid PCRE pattern in rule condition: "{pattern}".' =>
        'Patrón PCRE inválido en la condición de la regla: "{pattern}".',

    // Types.
    "Type ''{type}'' not found in registry. Available types: {types}." =>
        "El tipo ''{type}'' no se encontró en el registro. Tipos disponibles: {types}.",
    'Cannot guess type for value of type {type}.' =>
        'No se puede deducir el tipo de un valor de tipo {type}.',

    // Renderers.
    'No renderers registered for form elements.' =>
        'No hay renderizadores registrados para los elementos del formulario.',
    'No renderers registered for form widgets.' =>
        'No hay renderizadores registrados para los widgets del formulario.',
    'Element must be an instance of {expected} in CategorizationRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en CategorizationRenderer, se entregó {given}.',
    'Element must be an instance of {expected} in ControlRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en ControlRenderer, se entregó {given}.',
    'Element must be an instance of {expected} in GroupRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en GroupRenderer, se entregó {given}.',
    'Element must be an instance of {expected} in HorizontalLayoutRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en HorizontalLayoutRenderer, se entregó {given}.',
    'Element must be an instance of {expected} in LabelRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en LabelRenderer, se entregó {given}.',
    'Element must be an instance of {expected} in VerticalLayoutRenderer, {given} given.' =>
        'El elemento debe ser una instancia de {expected} en VerticalLayoutRenderer, se entregó {given}.',
    'The "renderer" in GroupRenderer must be an instance of FormRenderer.' =>
        'El "renderer" en GroupRenderer debe ser una instancia de FormRenderer.',
    'The "renderer" option in CategorizationRenderer must be an instance of FormRenderer.' =>
        'La opción "renderer" en CategorizationRenderer debe ser una instancia de FormRenderer.',
    'The "renderer" option in HorizontalLayoutRenderer must be an instance of FormRenderer.' =>
        'La opción "renderer" en HorizontalLayoutRenderer debe ser una instancia de FormRenderer.',
    'The "renderer" option in VerticalLayoutRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en VerticalLayoutRenderer debe ser una instancia de FormRendererInterface.',
    'The "renderer" option in ControlRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en ControlRenderer debe ser una instancia de FormRendererInterface.',
    'Field with property name "{name}" not found in form.' =>
        'No se encontró en el formulario el campo con nombre de propiedad "{name}".',
    'The "renderer" option in CheckboxWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en CheckboxWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'CheckboxWidgetRenderer: No choices found for multiple checkbox field "{name}"' =>
        'CheckboxWidgetRenderer: no se encontraron opciones para el campo checkbox múltiple "{name}"',
    'The "renderer" option in CollectionWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en CollectionWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'CollectionWidgetRenderer requires an ArraySchemaInterface property.' =>
        'CollectionWidgetRenderer requiere una propiedad ArraySchemaInterface.',
    'The "renderer" option in InputWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en InputWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'The "renderer" option in RadioWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en RadioWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'The "renderer" option in SelectWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en SelectWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'The "renderer" option in SliderWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en SliderWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'The "renderer" option in TextareaWidgetRenderer must be an instance of FormRendererInterface.' =>
        'La opción "renderer" en TextareaWidgetRenderer debe ser una instancia de FormRendererInterface.',
    'FormRules does not support offsetSet(). Use fill() to populate resolved rules.' =>
        'FormRules no soporta offsetSet(). Usa fill() para entregar las reglas resueltas.',
    'FormRules does not support offsetUnset().' =>
        'FormRules no soporta offsetUnset().',
];
