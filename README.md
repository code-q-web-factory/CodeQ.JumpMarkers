[![Latest Stable Version](https://poser.pugx.org/codeq/neos-link/v/stable)](https://packagist.org/packages/codeq/neos-link)
[![License](https://poser.pugx.org/codeq/neos-link/license)](LICENSE)

# CodeQ.JumpMarkers

This package allows linking to a content node as a jump marker and rendering a jump marker navigation.

## Installation

CodeQ.JumpMarkers is available via packagist run `composer require codeq/jumpmarkers`.
We use semantic versioning so every breaking change will increase the major-version number.

## Configuration

### 1. Extend content nodes with jump marker properties

Add the `CodeQ.JumpMarkers:Mixin.SectionConfiguration` mixin to any content NodeType 
that you would want to link in a jump marker navigation, or as a permalink with a hash.

E.g. this code adds such ability to every Content NodeType:

```yaml
'Neos.Neos:Content':
  superTypes:
    'CodeQ.JumpMarkers:Mixin.SectionConfiguration': true
```

![inspector editor](editor-demo.png)

### 2. Make content nodes available in the link search dialog of the backend

By default, Neos offers only document nodes for selection in its link search dialog. 
You can also allow content nodes with a section title or id set:

![linking demo](linking-demo.png)

To enable this feature, you must allow the SectionConfiguration in the link configuration 
nodeTypes option like this:

```
'YOUR.Site:Content.Text':
  superTypes:
    'Neos.Neos:Content': true
  ui:
    label: Text
    icon: file-text
    position: 200
    inlineEditable: true
  properties:
    text:
      type: string
      defaultValue: ''
      ui:
        inlineEditable: true
        inline:
          editorOptions:
            linking:
              nodeTypes: ['Neos.Neos:Document', 'CodeQ.JumpMarkers:Mixin.SectionConfiguration']
    link:
      type: string
      ui:
        label: 'Link'
        reloadPageIfChanged: false
        inspector:
          group: general
          editor: 'Neos.Neos/Inspector/Editors/LinkEditor'
          editorOptions:
            linking:
              nodeTypes: ['Neos.Neos:Document', 'CodeQ.JumpMarkers:Mixin.SectionConfiguration']
```

Our [ServicesNodesControllerAspect](Classes/Aspects/ServicesNodesControllerAspect.php) will make sure that
only content nodes with a jumpMarkerTitle or sectionId will be shown in the list.

### 3. Render the anchor id in the frontend

You can either render the id on the item itself with:
```
prototype(Vendor:Site) < prototype(Neos.Neos:ContentComponent) {
    id = CodeQ.JumpMarkers:NodeAnchorId
    renderer = afx`
        <section id={props.id}>
            ...
        </section
    `
```

or you add an anchor before with the `CodeQ.JumpMarkers:AnchorWrapper` processor to the same NodeType renderer
to insert an anchor tag with the given id before it.

E.g.:
```
prototype(Neos.Neos:Content) {
    @process.prependAnchor = CodeQ.JumpMarkers:AnchorWrapper
}
```

### 4. Render the jump marker navigation in the frontend

Here's an example of how to render a jump marker navigation

```
prototype(YOUR.Site:Integration.Organism.HashMenu) < prototype(Neos.Fusion:Component) {
    items = Neos.Fusion:Map {
        items = ${q(documentNode).children('main').children('[instanceof CodeQ.JumpMarkers:Mixin.SectionConfiguration][jumpMarkerTitle][jumpMarkerTitle!=""][_hidden != TRUE]').get()}
        itemName = 'node'
        itemRenderer = CodeQ.Link:Link {
            link = CodeQ.JumpMarkers:NodeAnchorId {
                node = ${node}
                @process.wrap = ${'#' + value}
            }
            label = ${q(node).property('jumpMarkerTitle')}
        }
    }

    renderer = afx`
        <ul class="hash-menu" @if.hasMultiple={Array.length(props.items) > 1}>
            <Neos.Fusion:Loop items={props.items} itemName="item" @children="itemRenderer">
                <li @if.hasName={String.trim(item.label)}>
                    <CodeQ.Link:Tag linkSource={item}>
                        {item.label}
                    </CodeQ.Link:Tag>
                </li>
            </Neos.Fusion:Loop>
        </ul>
    `
}
```

If your editors should be able to choose if the jump marker navigation is shown, 
use `CodeQ.JumpMarkers:Mixin.PageConfiguration` as a super type of the document and then
render the menu like this: `<YOUR.Site:Integration.Organism.HashMenu @if={q(node).property('showJumpMarkersMenu')}/>`

## Usage by editors

### Create jump marker navigation

Enable the jump marker for the content node you want to render in the jump marker navigation by setting a title 
and optionally a manual anchor id.

### Set permalink to content node

Choose some content node that you would like to link to, and fill in the `sectionId` property in the inspector.

Next, either (a) set a link to the content node by selecting the node in a link search dialog, 
or (b) copy the content node's uri to the clipboard via the "Copy Neos link" or "Copy link" button and paste it into 
the "insert link" field in the Aloha editor, and you're done!

## Migration

### Update from v2.0 to v3.0

The `CodeQ.JumpMarkers:Mixin.PageConfiguration` property `heroHashMenu` was replaces with `showJumpMarkersMenu` for clarity.
A migration is available: `./flow node:migrate --version 20230210175800`

### Update from v1.0 to v2.0

The property names have changed and some fallbacks were removed, so an automatic migration is not applied.
`CodeQ.JumpMarkers:Mixin.SectionConfiguration.DefaultDisabled` was removed,
`includeInHashMenu` was removed.
`hashMenuTitle` was renamed to `jumpMarkerTitle`, a migration for this is available: `./flow node:migrate --version 20230213183100`

## Credits

This package is heavily inspired by [Flownative.Anchorlinks v1.2.0](https://github.com/flownative/neos-anchorlinks)
with a lot of code copy-pasted, but with a different purpose. Thanks a lot to Flownative for their package!
