import { GET_ALBUM_LIST_REQUEST } from '../Types'


// ========>>>>>> GET ALBUM LIST REQUEST <<<<<<<<==========

export const getAlbumListRequest = (params) => {
    return {
        type: GET_ALBUM_LIST_REQUEST,
        params
    };
}
