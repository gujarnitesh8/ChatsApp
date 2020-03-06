import { put, call, takeEvery } from 'redux-saga/effects'

import Api from '../Services/api';
import {

    GET_NETWORK_STATUS_REQUEST,
    GET_NETWORK_STATUS_SUCCESS,
    GET_NETWORK_STATUS_FAILED
} from '../Types';

export const getNetWorkSaga = function* getNetWorkSaga({ params }) {
    try {
        yield put({ type: GET_NETWORK_STATUS_SUCCESS, payload: params });
    }
    catch (e) {
        console.log(e, 'error');
        yield put({ type: GET_NETWORK_STATUS_FAILED, payload: e });
    }
}
export function* commonSaga() {
    yield takeEvery(GET_NETWORK_STATUS_REQUEST, getNetWorkSaga);
}
export default commonSaga;