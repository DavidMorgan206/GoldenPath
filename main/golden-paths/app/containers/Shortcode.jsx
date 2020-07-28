import React from 'react';
import { Component } from 'react';
import fetch from 'node-fetch';
import PropTypes from 'prop-types';
import ManualNode from "./ManualNode";
import LandingNode from "./LandingNode";
import SessionSummary from "./SessionSummary";
import AffiliateNode from "./AffiliateNode";
import ListNode from "./ListNode";

export default class Shortcode extends Component {

  constructor(props) {
    super(props);
    this.state = {
      data: null,
    };
    this.handler = this.handler.bind(this);
    this.buyItem = this.buyItem.bind(this);
    this.skipItem = this.skipItem.bind(this);
    this.skipToSummary= this.skipToSummary.bind(this);
    this.refreshPublicPage = this.refreshPublicPage.bind(this);
  }

  refreshPublicPage(){
      fetch('http://localhost/wp-json/golden-paths/v1/publicendpoint?path_title=' + this.props.wpObject.path_title + '&cookie_id=' + this.props.wpObject.cookie_id)
          .then(data=> data.json())
          .then(data => this.setState({data}))
          .catch(function(e) {console.log('Error: ', e)});
  }
  componentDidMount(){
    this.refreshPublicPage();
  }

  handler() {
      this.refreshPublicPage();
  }

  skipToSummary()
  {
      this.fetchWrapper('&nextNode=Summary&action=skip')
  }

    fetchWrapper(command) {
      console.log('fetch command ' + command);
        fetch('http://localhost/wp-json/golden-paths/v1/publicendpoint?sessionId=' + this.state.data.sessionId + command + '&currentNode=' + this.state.data.nodeId,
            {
                method: 'PUT'
            })
            .then(response => response.json())
            .then(() => this.refreshPublicPage())
            .catch(e => console.log(e));
    }
  skipItem()
  {
      this.fetchWrapper('&nextNode=down&action=skip');
  }
  buyItem(price)
  {
      let command;
      command = '&nextNode=down&action=buy' + (this.state.data.allowCustomPrice ? '&customPrice=' + price : '');
      this.fetchWrapper(command);
  }

  render() {
    if(!this.state.data ) {
        return <div> </div>
    }
    else {
        switch (this.state.data.nodeTypeTitle) {
            case 'ManualNode':
                return <ManualNode state={this.state.data} skipToSummary={this.skipToSummary} skipItem={this.skipItem} buyItem={this.buyItem}/>;
            case 'LandingNode':
                return <LandingNode state={this.state.data} skipToSummary={this.skipToSummary} skipItem={this.skipItem} buyItem={this.buyItem}/>;
            case 'AffiliateNode' :
                return <AffiliateNode state={this.state.data} skipToSummary={this.skipToSummary} skipItem={this.skipItem} buyItem={this.buyItem}/>;
            case 'ListNode' :
                return <ListNode state={this.state.data} skipToSummary={this.skipToSummary} skipItem={this.skipItem} buyItem={this.buyItem} handler={this.handler}/>;
            case 'SessionSummary' :
                return <SessionSummary state={this.state.data} handler={this.handler}/>;
            default:
                throw new Error('Unsupported nodeTypeTitle ' + this.state.data.nodeTypeTitle);
        }
    }
  }
}


Shortcode.propTypes = {
  wpObject: PropTypes.object
};