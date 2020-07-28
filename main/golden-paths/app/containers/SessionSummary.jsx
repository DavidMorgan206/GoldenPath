import React from 'react';
import { Component } from 'react';
import PropTypes from 'prop-types';
import fetch from "node-fetch";

export default class SessionSummary extends Component {
    constructor(props) {
        super(props);
        this.startOver = this.startOver.bind(this);
        this.jumpToNode = this.jumpToNode.bind(this);
    }

    startOver()
    {
        let self = this;
        fetch('http://localhost/wp-json/golden-paths/v1/publicendpoint?sessionId=' + self.props.state.sessionId + '&nextNode=startOver&currentNode=' + self.props.state.nodeId,
            {
                method: 'PUT'
            })
            .then(response => response.json())
            .then(response => console.log(response))
            .then(() => this.props.handler())
            .catch(e => console.log(e));

    }

    jumpToNode(node_id)
    {

        let self = this;
        fetch('http://localhost/wp-json/golden-paths/v1/publicendpoint?sessionId=' + self.props.state.sessionId + '&nextNode=' + node_id + '&currentNode=' + self.props.state.nodeId,
            {
                method: 'PUT'
            })
            .then(response => response.json())
            .then(response => console.log(response))
            .then(() => this.props.handler())
            .catch(e => console.log(e));
    }

    indent(depth)
    {
        let ret = "";
        for(let i = 1; i < depth; i++){
            ret += '   ';
        }
        if(depth > 0) {
            ret +=' • ';
        }
        return ret;
    }

    render ()
    {
        const nodeList = [];

        let total = 0;
        let totalRow;

        for (let node of this.props.state.nodeList) {
            let nodeId = node.id;
            if (node.skipped == 0)
                total += node.price * node.quantity;

            let price;
            if (node.type == "ManualNode" && node.skipped == false) {
                price = this.props.state.currencySymbol + parseFloat(node.price == null ? 0 : node.price).toFixed(2);
            }

            let className = node.childOfListNode ? "notClickable " : "clickable ";
            className += node.buyable ? "buyable" : "notbuyable";

            nodeList.push(
                <tr className={className} skipped={node.skipped} key={nodeId} onClick={() => {
                     this.jumpToNode(nodeId)
                }}>
                    <td id="nodeTitle">{this.indent(node.treeDepth)}{node.title}</td>
                    <td id="nodePrice">{price}</td>
                </tr>)
        }

        if(this.props.state.displayTotalPriceOnSummary) {
            totalRow =
                <tr id="totalPrice"><td/><td>Total: {this.props.state.currencySymbol}{total.toFixed(2 )}</td></tr>
        }

        return (
            <div className="sessionSummary">
                <div className="flowTitle">
                    <h3 className="nodeTitle">{this.props.state.summaryTitle}</h3>
                    <div className="summaryBody" id="summaryBody" dangerouslySetInnerHTML={{ __html: this.props.state.summaryBody}}/>
                </div>

                <p className="sessionSummaryTitle">Summary</p>
                <table>
                        <col align="left"/>
                        <col align="left"/>
                        <col align="left"/>
                        <tbody>
                            {nodeList}
                            {totalRow}
                        </tbody>
                    </table>

                <button id="startOverButton" className="startOverButton" onClick={this.startOver}>Start Over</button>
            </div>
        )

    }

}

SessionSummary.propTypes = {
    state : PropTypes.object,
    handler : PropTypes.func
};