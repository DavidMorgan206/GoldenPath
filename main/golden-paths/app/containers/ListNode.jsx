import React from 'react';
import { Component } from 'react';
import PropTypes from 'prop-types';
import ListNodeChild from "./ListNodeChild";

export default class ListNode extends Component {

    constructor(props) {
        super(props);
        this.state = {choices : this.getUpdatedListChoices()};
        this.nextNodeExt = this.nextNodeExt.bind(this);
        this.onBuyChange = this.onBuyChange.bind(this);
        this.onPriceChange = this.onPriceChange.bind(this);
        this.getUpdatedListChoices = this.getUpdatedListChoices.bind(this);
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        //if we're rendering a new node, reset price (clearing user input from previous node)
        if(prevProps.state.nodeId !== this.props.state.nodeId) {
            this.setState({choices : this.getUpdatedListChoices()});
        }
    }
    getUpdatedListChoices() {
        const listChoices = [];
        var len = this.props.state.nodeList.length;
        for(var i = 0; i < len; i++){
            listChoices.push({
                nodeId: this.props.state.nodeList[i].nodeId,
                isChecked: !this.props.state.nodeList[i].skipped,
                quantity: this.props.state.nodeList[i].quantity,
                price: this.props.state.nodeList[i].customPrice
            });
        }

        return listChoices;
    }

    nextNodeExt()
    {
        console.log('In nextnodeext with choices ' + this.state.choices);

        let self = this;
        let url = 'http://localhost/wp-json/golden-paths/v1/publicendpoint?quantity=1&sessionId=' + self.props.state.sessionId + '&nextNode=next&action=next&currentNode=' + self.props.state.nodeId;

        fetch(url,
            {
                method: 'PUT',
                body: JSON.stringify(this.state.choices)
            })
            .then(response => response.json())
            .then(() => this.props.handler())
            .then(() => this.setState({choices: this.getUpdatedListChoices()})) //if we hit two listnodes in a row, we need to reinit the state
            .then(() => console.log('just sent nextnodeext and tried to update list choices'))
            .catch(e => console.log(e));

    }

    onPriceChange(nodeId, e)
    {
        let newChoices = this.state.choices;

        for(let i = 0; i < newChoices.length; i++)
        {
            if(parseInt(newChoices[i].nodeId) == parseInt(nodeId)){
                newChoices[i].price = parseFloat(e.target.value);
                newChoices[i].quantity = 1;
                newChoices[i].isChecked = true;
                break;
            }
        }

        this.setState({choices: newChoices});
    }

    onBuyChange(nodeId, e)
    {
        var newChoices = this.state.choices;

        for(var i = 0; i < newChoices.length; i++)
        {
            if(parseInt(newChoices[i].nodeId) == parseInt(nodeId)){
                //newChoices[i].isChecked =  (e.target.value.localeCompare('on') == 0);
                newChoices[i].isChecked =  !newChoices[i].isChecked;
                break;
            }
        }

        this.setState({choices: newChoices});
    }

    render ()
    {
        const nodeList = [];
        let linkPane = '';
        let skipToSummary = "";

        for (let node of this.props.state.nodeList) {
            let existingChoices;
            existingChoices = this.state.choices.filter((e) => e.nodeId === node.nodeId)[0];
            if(!existingChoices) {
                console.log('no matching existing choice, skip');
            }
            else {
                nodeList.push(<ListNodeChild currencySymbol={this.props.state.currencySymbol}
                                             price={parseFloat(existingChoices.price)} data={node} key={node.nodeId}
                                             isChecked={existingChoices.isChecked}
                                             onPriceChange={this.onPriceChange.bind(this, node.nodeId)}
                                             onBuyChange={this.onBuyChange.bind(this, node.nodeId)}
                />);
            }
        }

        if(this.props.state.linkPaneHtml)
        {
            linkPane = <td width="300px">
                <div id="linkPaneHtml" className="linkPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.linkPaneHtml}}/>
                <br/>
            </td>
        }

        if(this.props.state.displaySkipToSummary) {
            skipToSummary = <button id="skipToSummary" className="skipToSummaryButton" onClick={this.props.skipToSummary}>Skip to Summary</button>
        }

        //add brs at end to not overlap next button with some themes scroll up pop over
        return (
            <div className="listNode">
                <div className="gpathsSingleItemNode">
                    <h3 className="nodeTitle">
                        {this.props.state.heading}
                    </h3>

                    {linkPane}

                    <div id="bodyPaneHtml" className="bodyPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.bodyPaneHtml}}/>
                </div>

                <table className="listNodeChildrenTable">
                {nodeList}
                </table>


                <div className="gpathsButtonsSeperator"/>
                <button id="nextButton" className="next-button" onClick={this.nextNodeExt}>Next</button>
                <br/>
                {skipToSummary}
                <br/>
                <br/><br/><br/>
            </div>
                )
    }
}

ListNode.propTypes = {
    state : PropTypes.object,
    handler : PropTypes.func,
    buyItem : PropTypes.func,
    skipToSummary: PropTypes.func
}