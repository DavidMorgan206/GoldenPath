import React from 'react';
import {shallow} from 'enzyme';
import {shallowToJson} from 'enzyme-to-json';

import SessionSummary from '../SessionSummary';

describe('SessionSummary', () => {
  it('Should display title', () => {
      const output = shallow(
          <SessionSummary sessionTitle="Test Session Summary Title"/>
      );
      expect(shallowToJson(output)).toMatchSnapshot();
  })
});
