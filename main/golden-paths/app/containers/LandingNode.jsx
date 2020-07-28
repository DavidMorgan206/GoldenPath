import React from 'react';
import { Component } from 'react';
import PropTypes from 'prop-types';

export default class LandingNode extends Component {
    constructor(props) {
        super(props);
    }

    render ()
    {
        let skipButton = "";
        let linkPane = "";
        let skipToSummary = "";

        if(this.props.state.skippable)
        {
            skipButton = <button id="skipButton" onClick={this.props.skipItem} className="skip-button">Skip</button>
        }
        if(this.props.state.linkPaneHtml)
        {
            linkPane = <div id="linkPaneHtml" className="linkPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.linkPaneHtml}}/>
        }
        if(this.props.state.displaySkipToSummary) {
            skipToSummary = <button id="skipToSummary" className="skipToSummaryButton" onClick={this.props.skipToSummary}>Skip to Summary</button>
        }

        return (
            <div className="gpathsSingleItemNode">
                <h3 className="nodeTitle">{this.props.state.heading}</h3>

                {linkPane}
                <div id="bodyPaneHtml" className="bodyPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.bodyPaneHtml}}/>

                <button id="nextButton" className="startFlowButton" onClick={this.props.buyItem}>Next</button>
                {skipButton}
                {skipToSummary}
            </div>
        )
    }
}

LandingNode.propTypes = {
    state : PropTypes.object,
    buyItem: PropTypes.func,
    skipToSummary: PropTypes.func,
    skipItem: PropTypes.func,
};