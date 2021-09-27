import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {Button} from '@neos-project/react-ui-components';
import {connect} from 'react-redux';
import {selectors} from '@neos-project/neos-ui-redux-store';
import I18n from '@neos-project/neos-ui-i18n';
import {$get} from 'plow-js';

@connect(state => ({
    documentNode: selectors.CR.Nodes.documentNodeSelector(state),
    focusedNode: selectors.CR.Nodes.focusedSelector(state),
    transientSectionId: $get('sectionId.value', selectors.UI.Inspector.transientValues(state))
}))
export default class AnchorView extends Component {
    static propTypes = {
        focusedNode: PropTypes.object,
        documentNode: PropTypes.object,
        transientSectionId: PropTypes.string
    };

    state = {
    	// states: 'default', 'loading' 'copied'
		copyNeosLinkState: 'default',
		copyUriState: 'default',
    };

    getSectionId = () => this.props.transientSectionId || $get('properties.sectionId', this.props.focusedNode)

	copyNeosLinkToClipboard = () => {
		const documentNodeIdentifier = $get('identifier', this.props.documentNode);
		const link = 'node://' + documentNodeIdentifier + '#' + this.getSectionId();
		this.copyToClipboard(link);
		this.setState({copyNeosLinkState: 'copied'});
	};

	copyUriToClipboard = () => {
		this.setState({copyUriState: 'loading'});
		const redirectUri = $get('uri', this.props.documentNode).replace('neos/preview', 'neos/jump-markers-node-to-uri');
		console.info(redirectUri);
		fetch(redirectUri).then(response => {
			this.copyToClipboard(response.url + '#' + this.getSectionId());
			console.info(response);
			console.info(response.url + '#' + this.getSectionId());
			this.setState({copyUriState: 'copied'});
		});
	};

	copyToClipboard = (link) => {
		const textArea = document.createElement('textarea');
		textArea.innerText = link;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		textArea.parentNode.removeChild(textArea);
	};

	getIcon = (state) => {
		return (
			<div style={{
				display: 'inline-block',
				width: '16px',
				height: '16px',
				fill: 'white',
				marginLeft: '8px',
				verticalAlign: 'sub'
			}}>
				{state === 'default' ?
					<svg viewBox="0 0 896 1024" width="100%" xmlns="http://www.w3.org/2000/svg">
						<path
							d="M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z"/>
					</svg>
					: ''}
				{state === 'loading' ?
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="spinner" role="img"
						 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
						 className="svg-inline--fa fa-spinner fa-w-16 fa-spin fa-lg">
						<path fill="currentColor"
							  d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z"
							  className=""></path>
					</svg>
					: ''}
				{state === 'copied' ?
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="clipboard-check"
						 className="svg-inline--fa fa-clipboard-check fa-w-12" role="img"
						 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
						<path fill="currentColor"
							  d="M336 64h-80c0-35.3-28.7-64-64-64s-64 28.7-64 64H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM192 40c13.3 0 24 10.7 24 24s-10.7 24-24 24-24-10.7-24-24 10.7-24 24-24zm121.2 231.8l-143 141.8c-4.7 4.7-12.3 4.6-17-.1l-82.6-83.3c-4.7-4.7-4.6-12.3.1-17L99.1 285c4.7-4.7 12.3-4.6 17 .1l46 46.4 106-105.2c4.7-4.7 12.3-4.6 17 .1l28.2 28.4c4.7 4.8 4.6 12.3-.1 17z"></path>
					</svg>
					: ''}
			</div>
		);
	};

    render() {
        return this.getSectionId() && (
        	<div style={{
				display: 'flex',
				justifyContent: 'space-between'
			}}>
				<Button style="brand" onClick={this.copyNeosLinkToClipboard}>
					<I18n
						id={`CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-neos-link`}
						fallback="Copy Neos link"
					/>
					{this.getIcon(this.state.copyNeosLinkState)}
				</Button>
				<Button style="brand" onClick={this.copyUriToClipboard}>
					<I18n
						id={`CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-uri`}
						fallback="Copy link"
					/>
					{this.getIcon(this.state.copyUriState)}
				</Button>
			</div>
        );
    }
}
